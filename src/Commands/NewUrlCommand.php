<?php declare(strict_types=1);

namespace blitzik\Routing\Commands;

use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Command\Command;
use blitzik\Routing\Services\UrlGenerator;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Utils\Validators;
use blitzik\Routing\Url;

class NewUrlCommand extends Command
{
    /** @var EntityManager */
    private $em;

    /** @var EntityRepository */
    private $urlRepository;


    public function __construct(EntityManager $entityManager)
    {
        parent::__construct();

        $this->em = $entityManager;
        $this->urlRepository = $entityManager->getRepository(Url::class);
    }


    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('urls:new')
             ->setDescription('Creates new Url');
    }


    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        do {
            if (!isset($urlPath)) {
                $urlPath = $this->askUrlPath($helper, $input, $output);
            }

            if (!isset($presenter)) {
                $presenter = $this->askPresenter($helper, $input, $output);
            }

            if (!isset($internalID)) {
                $internalID = null;
                $internalIDConfirmation = new ConfirmationQuestion('Would you like to set Internal ID?', true);
                if ($helper->ask($input, $output, $internalIDConfirmation)) {
                    $internalID = $this->askInternalID($helper, $input, $output);
                }
            }

            $output->writeln(
                sprintf(
                    'Summary:
                     Url path: %s
                     Presenter: %s
                     internal ID: %s',
                    $urlPath,
                    $presenter,
                    $internalID === null ? 'null' : $internalID
                )
            );

            $anotherUrl = new ConfirmationQuestion('Do you want to save your Url?', true);
            if (!$helper->ask($input, $output, $anotherUrl)) {
                return;
            }

            try {
                $url = UrlGenerator::create($urlPath, $presenter, null, $internalID);
                $url = $this->em->safePersist($url);
                if ($url === false) {
                    $output->writeln(sprintf('Somebody has recently created an Url with path "%s".', $urlPath));

                    $changeQuestion = new ConfirmationQuestion('Do you want to change that Url path?', true);
                    if ($helper->ask($input, $output, $changeQuestion)) {
                        $urlPath = null;
                        continue;
                    }

                    $output->writeln(sprintf('Your Url couldn\'t have been saved because url with path "%s" already exists.', $urlPath));
                    return 1;
                }

                $output->writeln('New Url has been SUCCESSFULLY created!');

                $continueQuestion = new ConfirmationQuestion('Would you like to create another one?', true);
                if ($helper->ask($input, $output, $continueQuestion)) {
                    $urlPath = null;
                    $presenter = null;
                    $internalID = null;
                    continue;
                }

                return 0;

            } catch (\Exception $e) {
                $output->writeLn("That's bad. An Error occurred: <error>{$e->getMessage()}</error>");
                return -1;
            }

        } while (true);
    }


    private function askUrlPath(QuestionHelper $helper, InputInterface $input, OutputInterface $output)
    {
        $question = new Question('Url path: ');
        $question->setValidator(function ($answer) {
            if (!Validators::is($answer, 'unicode:1..')) {
                throw new \RuntimeException('The Url path must be string');
            }

            try {
                $url = $this->urlRepository->findOneBy(['urlPath' => $answer]);

            } catch (\Exception $e) {
                throw new \RuntimeException('An error occurred while searching for url path. Try it again.');
            }

            if ($url !== null) {
                throw new \RuntimeException(sprintf('Url with path "%s" already exists', $answer));
            }

            return $answer;
        });

        $question->setMaxAttempts(null); // unlimited number of attempts

        return $helper->ask($input, $output, $question);
    }


    private function askPresenter(QuestionHelper $helper, InputInterface $input, OutputInterface $output)
    {
        $question = new Question('Presenter: ');
        $question->setValidator(function ($answer) {
            if (!Validators::is(trim($answer), 'unicode:1..')) {
                throw new \RuntimeException('The Presenter must be non-empty string');
            }

            if (!preg_match('~^(?P<modulePresenter>(?:(?:[A-Z][a-zA-Z]*):)*(?:[A-Z][a-zA-Z]*)):(?P<action>[a-z][a-zA-Z]*)$~', $answer)) {
                throw new \RuntimeException('Wrong format of Presenter. Format is (Module:)*(Presenter):(action). Check your first letters at all parts of Presenter');
            }

            return $answer;
        });

        $question->setMaxAttempts(null); // unlimited number of attempts

        return $helper->ask($input, $output, $question);
    }


    private function askInternalID(QuestionHelper $helper, InputInterface $input, OutputInterface $output)
    {
        $question = new Question('Internal ID: ');
        $question->setValidator(function ($answer) {
            if (!Validators::is(trim($answer), 'numericint:1..')) {
                throw new \RuntimeException('The Internal ID must be positive integer number.');
            }

            return $answer;
        });

        $question->setMaxAttempts(null); // unlimited number of attempts

        return $helper->ask($input, $output, $question);
    }

}