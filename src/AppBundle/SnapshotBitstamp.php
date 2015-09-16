<?php
namespace AppBundle;

/**
 * Snapshots Bitstamp data.
 */
class SnapshotBitstamp
{
    protected $state;

    protected $logger;

    protected $secrets;

    protected $keenio;

    protected $balance;

    const EVENT_NAME = 'bitstamp_balance';

    const PROJECT_ID_SECRET_NAME = 'KEEN_PROJECT_ID';

    const WRITE_KEY_SECRET_NAME = 'KEEN_WRITE_KEY';

    const READ_KEY_SECRET_NAME = 'KEEN_READ_KEY';

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
        API\Bitstamp\PrivateAPI\Balance $balance
    )
    {
        $this->logger = $logger;
        $this->secrets = $secrets;

        $this->keenio = $keenio->factory();
        // Initialise the secrets for Keen.
        $this->keenio->setProjectID($this->secrets->get(self::PROJECT_ID_SECRET_NAME));
        $this->keenio->setWriteKey($this->secrets->get(self::WRITE_KEY_SECRET_NAME));
        $this->keenio->setReadKey($this->secrets->get(self::READ_KEY_SECRET_NAME));

        $this->balance = $balance;
    }

    /**
     * Updates the internal state of the snapshot to represent Bitstamp data.
     * @return SnapshotBitstamp
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
        $this->logger->info('Persisting state to Keen IO', ['projectID' => $this->secrets->get(self::PROJECT_ID_SECRET_NAME)]);
        $this->keenio->addEvent(self::EVENT_NAME, $this->state);
    }
}
