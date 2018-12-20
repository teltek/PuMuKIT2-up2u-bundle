<?php

namespace Pumukit\Up2u\WebTVBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class BlockUnsyncedCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setName('geant:syncfeed:blockold')
        ->setDescription('Block Unsynced mm objects  on PuMuKIT.')
        ->setHelp($this->getCommandHelpText())
        ->addArgument(
                'url',
                InputArgument::OPTIONAL,
                'If set, force the feed URL for FeedSyncClientService.'
            )
        ->addArgument(
                'tag',
                InputArgument::OPTIONAL,
                'If set, name to mark the new multimedia object created.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $formatter = $this->getHelper('formatter');
        $text = $this->getCommandASCIIHeader();
        $text .= "\nAt ".(new \DateTime())->format('c');
        $formattedBlock = $formatter->formatBlock($text, 'comment', true);
        $output->writeln($formattedBlock);
        //EXECUTE SERVICE
        $feedSyncService = $this->getContainer()->get('pumukit_web_tv.geant.feedsync');

        $tag = $input->getArgument('tag');
        $customUrl = $input->getArgument('url');
        if ($customUrl) {
            $feedSyncService->setFeedUrl($customUrl);
        }

        $startTime = new \MongoDate(strtotime("-7 days"));
        $output->writeln("\nSYNC FINISHED: Blocking Unsynced from " . date('Y-M-d h:i:s', $startTime->sec). "..");
        $feedSyncService->blockUnsynced($output, $startTime, $tag);
        //SHUTDOWN HAPPILY
    }

    protected function getCommandHelpText()
    {
        return <<<EOT
Command to block unsynced objects.

The --force parameter has to be used to actually drop the database.

EOT;
    }

    protected function getCommandASCIIHeader()
    {
        return <<<EOT
:::Command to Block Unsynced objects in the PuMuKIT Database:::
EOT;
    }
}
