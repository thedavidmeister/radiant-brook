<?php
namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SnapshotBitstampCommand extends Command
{
  public function __construct(\AppBundle\SnapshotBitstamp $snapshot)
  {
    $this->snapshot = $snapshot;
    parent::__construct();
  }

  protected function configure() {
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
