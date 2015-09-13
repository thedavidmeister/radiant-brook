<?php
namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command to snapshot Bitstamp data.
 */
class SnapshotBitstampCommand extends Command
{
    /**
     * DI Constructor.
     * @param \AppBundle\SnapshotBitstamp $snapshot
     */
    public function __construct(\AppBundle\SnapshotBitstamp $snapshot)
    {
        $this->snapshot = $snapshot;
        parent::__construct();
    }

    protected function configure()
    {
        $this
        ->setName('snapshot:bitstamp')
        ->setDescription('Snapshot bistamp state')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->snapshot
        ->updateState()
        ->persist()
        ;
    }
}
