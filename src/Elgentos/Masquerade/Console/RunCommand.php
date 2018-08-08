<?php

namespace Elgentos\Masquerade\Console;

use Phar;
use Symfony\Component\Console\Command\Command;
use Noodlehaus\Config;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Faker\Factory as FakerFactory;
use Symfony\Component\Console\Helper\ProgressBar;
use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Component\Process\Process;
use Jack\Symfony\ProcessManager;

class RunCommand extends Command
{
    protected $config;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    protected $platformName;
    protected $locale;

    const LOGO = '                              
._ _  _. _ _.    _ .__. _| _  
| | |(_|_>(_||_|(/_|(_|(_|(/_ 
            |
                   by elgentos';

    const VERSION = '0.1.1';

    /**
     * @var \Illuminate\Database\Connection
     */
    protected $db;
    protected $group = [];
    protected $fakerInstances = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run masquerade for a specific platform and group(s)';

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription($this->description)
            ->addOption('platform', null, InputOption::VALUE_OPTIONAL)
            ->addOption('driver', null, InputOption::VALUE_OPTIONAL, 'Database driver [mysql]')
            ->addOption('database', null, InputOption::VALUE_OPTIONAL)
            ->addOption('username', null, InputOption::VALUE_OPTIONAL)
            ->addOption('password', null, InputOption::VALUE_OPTIONAL)
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Database host [localhost]')
            ->addOption('prefix', null, InputOption::VALUE_OPTIONAL, 'Database prefix [empty]')
            ->addOption('locale', null, InputOption::VALUE_OPTIONAL, 'Locale for Faker data [en_US]')
            ->addOption('group', null, InputOption::VALUE_OPTIONAL, 'Which groups to run masquerade on [all]')
            ->addOption('charset', null, InputOption::VALUE_OPTIONAL, 'Database charset [utf8]');

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->setup();

        $this->output->writeln(self::LOGO);
        $this->output->writeln('                        v' . self::VERSION);

        if (count($this->group) !== 1) {
            // Spawn subprocesses
            $processes = [];

            foreach ($this->config as $groupName => $tables) {
                if (!empty($this->group) && !in_array($groupName, $this->group)) {
                    continue;
                }
                $processes[] = new Process('bin/masquerade run --group=' . $groupName);
            }

            $processManager = new ProcessManager();
            $output->writeln('Starting max ' . count($this->config) . ' processes..');
            $processManager->runParallel($processes, count($this->config), 1000, function ($type, $output, $process) { echo $output; });
        } else {
            // Fake data
            foreach ($this->config as $groupName => $tables) {
                if (!empty($this->group) && !in_array($groupName, $this->group)) {
                    continue;
                }
                foreach ($tables as $tableName => $table) {
                    $table['name'] = $tableName;
                    $this->fakeData($table);
                }
            }
        }

        $this->output->writeln('Done anonymizing');
    }

    /**
     * @param $table
     */
    private function fakeData($table)
    {
        if (!$this->db->getSchemaBuilder()->hasTable($table['name'])) {
            $this->output->writeln('Table ' . $table['name'] . ' does not exist.');
            return;
        }

        foreach ($table['columns'] as $columnName => $columnData) {
            if (!$this->db->getSchemaBuilder()->hasColumn($table['name'], $columnName)) {
                unset($table['columns'][$columnName]);
                $this->output->writeln('Column ' . $columnName . ' in table ' . $table['name'] . ' does not exist; skip it.');
            }
        }

        $this->output->writeln('');
        $this->output->writeln('Updating ' . $table['name']);

        $totalRows = $this->db->table($table['name'])->count();
        $progressBar = new ProgressBar($this->output, $totalRows);
        $progressBar->setRedrawFrequency($this->calculateRedrawFrequency($totalRows));
        $progressBar->start();

        $primaryKey = array_get($table, 'pk', 'entity_id');

        $this->db->table($table['name'])->orderBy($primaryKey)->chunk(100, function ($rows) use ($table, $progressBar, $primaryKey) {
            // Null columns before run to avoid integrity constrains errors
            foreach ($table['columns'] as $columnName => $columnData) {
                if (array_get($columnData, 'nullColumnBeforeRun', false)) {
                    $this->db->table($table['name'])->update([$columnName => null]);
                }
            }

            foreach ($rows as $row) {
                $updates = [];
                foreach ($table['columns'] as $columnName => $columnData) {
                    $formatter = array_get($columnData, 'formatter.name');
                    $formatterData = array_get($columnData, 'formatter');
                    $providerClassName = array_get($columnData, 'provider', false);

                    if (!$formatter) {
                        $formatter = $formatterData;
                        $options = [];
                    } else {
                        $options = array_values(array_slice($formatterData, 1));
                    }

                    if (!$formatter) continue;

                    if ($formatter == 'fixed') {
                        $updates[$columnName] = array_first($options);
                        continue;
                    }

                    try {
                        $updates[$columnName] = $this->getFakerInstance($columnData, $providerClassName)->{$formatter}(...$options);
                    } catch (\InvalidArgumentException $e) {
                        // If InvalidArgumentException is thrown, formatter is not found, use null instead
                        $updates[$columnName] = null;
                    }
                }
                $this->db->table($table['name'])->where($primaryKey, $row->{$primaryKey})->update($updates);
                $progressBar->advance();
            }
        });

        $progressBar->finish();

        $this->output->writeln('');
    }

    /**
     * @throws \Exception
     */
    private function setup()
    {
        if (file_exists('config.yaml')) {
            $databaseConfig = new Config('config.yaml');
        }

        $this->platformName = $databaseConfig['platform'] ?? $this->input->getOption('platform');

        if (!$this->platformName) {
            throw new \Exception('No platformName set, use option --platform or set it in config.yaml');
        }

        // Get default config
        $config = new Config($this->getConfigFiles($this->platformName));
        $this->config = $config->all();

        // Get custom config
        if (file_exists('config') && is_dir('config')) {
            $customConfig = new Config(sprintf('config/%s', $this->platformName));
            $this->config = array_merge($config->all(), $customConfig->all());
        }

        $host = $databaseConfig['host'] ?? $this->input->getOption('host') ?? 'localhost';
        $driver = $databaseConfig['driver'] ?? $this->input->getOption('driver') ?? 'mysql';
        $database = $databaseConfig['database'] ?? $this->input->getOption('database');
        $username = $databaseConfig['username'] ?? $this->input->getOption('username');
        $password = $databaseConfig['password'] ?? $this->input->getOption('password');
        $prefix = $databaseConfig['prefix'] ?? $this->input->getOption('prefix');
        $charset = $databaseConfig['charset'] ?? $this->input->getOption('charset') ?? 'utf8';

        $errors = [];
        if (!$host) {
            $errors[] = 'No host defined';
        }
        if (!$database) {
            $errors[] = 'No database defined';
        }
        if (!$username) {
            $errors[] = 'No username defined';
        }
        if (count($errors) > 0) {
            throw new \Exception(implode(PHP_EOL, $errors));
        }

        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => $driver,
            'host'      => $host,
            'database'  => $database,
            'username'  => $username,
            'password'  => $password,
            'prefix'    => $prefix,
            'charset'   => $charset,
        ]);

        $this->db = $capsule->getConnection();
        $this->db->statement('SET FOREIGN_KEY_CHECKS=0');

        $this->locale = $databaseConfig['locale'] ?? $this->input->getOption('locale') ?? 'en_US';

        $this->group = array_filter(array_map('trim', explode(',', $this->input->getOption('group'))));
    }

    /**
     * @param $columnName
     * @param $columnData
     * @param bool $providerClassName
     * @return mixed
     * @throws \Exception
     * @internal param bool $provider
     */
    private function getFakerInstance($columnData, $providerClassName = false)
    {
        $fakerInstance = FakerFactory::create($this->locale);

        $provider = false;
        if ($providerClassName) {
            $provider = new $providerClassName($fakerInstance);
        }

        if (is_object($provider)) {
            if (!$provider instanceof \Faker\Provider\Base) {
                throw new \Exception('Class ' . get_class($provider) . ' is not an instance of \Faker\Provider\Base');
            }
            $fakerInstance->addProvider($provider);
        }

        if (array_get($columnData, 'unique', false)) {
            $fakerInstance->unique();
        }
        if (array_get($columnData, 'optional', false)) {
            $fakerInstance->optional();
        }
        if (array_get($columnData, 'valid', false)) {
            $fakerInstance->valid();
        }

        return $fakerInstance;
    }

    /**
     * @return bool
     */
    private function isPhar() {
        return strlen(Phar::running()) > 0 ? true : false;
    }

    /**
     * @param $platformName
     * @return array
     */
    private function getConfigFiles($platformName)
    {
        // Unfortunately, glob() does not work when using a phar and hassankhan/config relies on glob.
        // Therefore, we have to scan the dir ourselves when using the phar
        if ($this->isPhar()) {
            $configDir = 'phar://masquerade.phar/src/config/' . $platformName;
        } else {
            $configDir = __DIR__ . '/../../../config/' . $platformName;
        }

        $files = array_slice(scandir($configDir), 2);

        return array_map(function ($file) use ($configDir) {
            return $configDir . '/' . $file;
        }, $files);
    }

    private function calculateRedrawFrequency($totalRows)
    {
        $percentage = 10;

        if ($totalRows < 100) {
            $percentage = 10;
        } elseif ($totalRows < 1000) {
            $percentage = 1;
        } elseif ($totalRows < 10000) {
            $percentage = 0.1;
        } elseif ($totalRows < 100000) {
            $percentage = 0.01;
        } elseif ($totalRows < 1000000) {
            $percentage = 0.001;
        }

        return ceil($totalRows * $percentage);
    }
}
