<?php

namespace Babymarkt\Symfony\Influxdb2Bundle\Command;

use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientNotFoundException;
use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientRegistry;
use InfluxDB2\ApiException;
use InfluxDB2\Model\OnboardingRequest;
use InfluxDB2\Service\SetupService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'babymarkt-influxdb2:setup',
    description: 'Set up initial user, org and bucket.'
)]
class SetupCommand extends Command
{
    public function __construct(protected ClientRegistry $registry)
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->addOption(
            name: 'client',
            shortcut: 'c',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The client to use.',
            default: 'default'
        );

        $this->addOption(
            name: 'user',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'Initial username'
        );

        $this->addOption(
            name: 'password',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'Initial user password'
        );

        $this->addOption(
            name: 'org',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'Initial organisation name'
        );

        $this->addOption(
            name: 'token',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'Initial admin token'
        );

        $this->addOption(
            name: 'bucket',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'Initial bucket'
        );

        $this->addOption(
            name: 'duration',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'Initial bucket duration',
            default: 0
        );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $client = $this->registry->getClient($input->getOption('client'));
        } catch (ClientNotFoundException $e) {
            $io->error($e->getMessage());
            return 3;
        }

        /** @var SetupService $setupService */
        $setupService = $client->createService(SetupService::class);

        $isOnboarding = $setupService->getSetup();
        if (!$isOnboarding->getAllowed()) {
            $io->info('Setup already completed.');
            return 1;
        }

        $onboardingRequest = (new OnboardingRequest())
            ->setUsername($this->askForUser($io, $input->getOption('user')))
            ->setPassword($this->askForPassword($io, $input->getOption('password')))
            ->setToken($this->askForToken($io, $input->getOption('token')))
            ->setOrg($this->askForOrganisation($io, $input->getOption('org')))
            ->setBucket($this->askForBucket($io, $input->getOption('bucket')))
            ->setRetentionPeriodSeconds($this->askForDuration($io, $input->getOption('duration')));

        try {
            $onboardingResponse = $setupService->postSetup($onboardingRequest);

            if ($onboardingResponse->getCode() === "201") {
                $token = $onboardingResponse->getAuth()->getToken();
                $io->success('Setup completed!');
                $io->writeln('Token: ' . $token);
                return 0;
            }
        } catch (ApiException $e) {
            $io->error($e->getResponseBody());
        }

        return 2;
    }

    /**
     * @param SymfonyStyle $io
     * @param string|null $defaultUser
     * @return string
     */
    protected function askForUser(SymfonyStyle $io, ?string $defaultUser): string
    {
        return (string)$io->ask('User', (string)$defaultUser, function ($user) {
            if (empty($user)) {
                throw new \RuntimeException('You must enter a user.');
            }
            return $user;
        });
    }

    /**
     * @param SymfonyStyle $io
     * @param string|null $inputPassword
     * @return string
     */
    protected function askForPassword(SymfonyStyle $io, ?string $inputPassword): string
    {
        if (empty($inputPassword)) {
            return (string) $io->askHidden('New password', function (string $password) {
                if (strlen($password) < 8) {
                    throw new \RuntimeException('The password must be at least 8 character long.');
                }
                return $password;
            });
        }

        return $inputPassword;
    }

    /**
     * @param SymfonyStyle $io
     * @param string|null $defaultOrg
     * @return string
     */
    protected function askForOrganisation(SymfonyStyle $io, ?string $defaultOrg): string
    {
        return (string)$io->ask('Organisation', (string)$defaultOrg, function ($org) {
            if (empty($org)) {
                throw new \RuntimeException('You must enter a organisation name.');
            }
            return $org;
        });
    }

    /**
     * @param SymfonyStyle $io
     * @param string|null $defaultBucket
     * @return string
     */
    protected function askForBucket(SymfonyStyle $io, ?string $defaultBucket): string
    {
        return (string)$io->ask('Bucket', (string)$defaultBucket, function ($bucket) {
            if (empty($bucket)) {
                throw new \RuntimeException('You must enter a bucket name.');
            }
            return $bucket;
        });
    }

    /**
     * @param SymfonyStyle $io
     * @param string|null $defaultToken
     * @return string
     */
    protected function askForToken(SymfonyStyle $io, ?string $defaultToken): string
    {
        return (string)$io->ask('Token', $defaultToken);
    }

    /**
     * @param SymfonyStyle $io
     * @param int|null $duration
     * @return int
     */
    protected function askForDuration(SymfonyStyle $io, ?int $duration): int
    {
        return (int)$io->ask('Duration in seconds', (int)$duration, function ($v) {
            if (!is_numeric($v)) {
                throw new \RuntimeException('The duration must be numeric');
            }

            if ($v < 0) {
                throw new \RuntimeException('The duration must be greater than or equal to 0');
            }
            return $v;
        });
    }

}
