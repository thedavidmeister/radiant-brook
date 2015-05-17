<?php
namespace AppBundle;

/**
 * Snapshots Bitstamp data.
 */
class SnapshotBitstamp
{
    protected $state;

    const EVENT_NAME = 'bitstamp_balance';

    /**
     * Constructor for DI.
     *
     * @param \KeenIO\Client\KeenIOClient     $keenio
     * @param \Psr\Log\LoggerInterface        $logger
     * @param Secrets                         $secrets
     * @param API\Bitstamp\PrivateAPI\Balance $balance
     */
    public function __construct(
    \KeenIO\Client\KeenIOClient $keenio,
    \Psr\Log\LoggerInterface $logger,
    Secrets $secrets,
    API\Bitstamp\PrivateAPI\Balance $balance)
    {
        $this->logger = $logger;
        $this->secrets = $secrets;

        $this->keenio = $keenio->factory();
        // Initialise the secrets for Keen.
        $this->keenio->setProjectID($this->secrets->get('keen/projectID'));
        $this->keenio->setWriteKey($this->secrets->get('keen/writeKey'));
        $this->keenio->setReadKey($this->secrets->get('keen/readKey'));

        $this->balance = $balance;
    }

    /**
     * Updates the internal state of the snapshot to represent Bitstamp data.
     * @return this
     */
    public function updateState()
    {
        $this->state = $this->balance->data();

        return $this;
    }

    /**
     * Persists the internal state to KeenIO as an event.
     */
    public function persist()
    {
        $this->logger->info('Persisting state to Keen projectID: ' . $this->secrets->get('keen/projectID'));
        $this->keenio->addEvent(self::EVENT_NAME, $this->state);
    }
}
