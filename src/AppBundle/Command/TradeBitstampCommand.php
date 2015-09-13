<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command to execute Bitstamp trades.
 */
class TradeBitstampCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('trade:bitstamp')
            ->setDescription('trade pairs on bitstamp')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tradePairs = $this->getContainer()->get('bitstamp.trade_pairs');

        $output->writeln($tradePairs->execute());
    }
}
