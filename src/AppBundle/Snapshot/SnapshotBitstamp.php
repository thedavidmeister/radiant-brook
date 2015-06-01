<?php
namespace AppBundle\Snapshot;

/**
 * Snapshots Bitstamp data.
 */
abstract class SnapshotBitstamp
{
    protected $logger;

    protected $keenio;

    const PROJECT_ID_SECRET_NAME = 'KEEN_PROJECT_ID';

    const WRITE_KEY_SECRET_NAME = 'KEEN_WRITE_KEY';

    const READ_KEY_SECRET_NAME = 'KEEN_READ_KEY';

    protected function setImmutableDepenency($name, $dependency) {
        if (isset($this->$name)) {
            throw new \Exception('Dependency ' . $dependency . ' is already set.');
        }
        $this->$name = $dependency;
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger) {
        $this->setImmutableDepenency('logger', $logger);
    }

    public function setKeenIOClient(\KeenIO\Client\KeenIOClient $keenio)
    {
        $keenioFactory = $keenio->factory();
        $this->setImmutableDepenency('keenio', $keenioFactory);

        // Initialise the secrets for Keen.
        $this->keenio->setProjectID($this->secrets->get(self::PROJECT_ID_SECRET_NAME));
        $this->keenio->setWriteKey($this->secrets->get(self::WRITE_KEY_SECRET_NAME));
        $this->keenio->setReadKey($this->secrets->get(self::READ_KEY_SECRET_NAME));
    }

    public function setSecrets(Secrets $secrets)
    {
        $this->setImmutableDepenency('secrets', $secrets);
    }

    public function data()
    {
        return $this->dataProvider->data();
    }

    /**
     * Persists the internal state to KeenIO as an event.
     */
    public function snap()
    {
        $this->logger->info('Persisting data to Keen IO', ['projectID' => $this->secrets->get(self::PROJECT_ID_SECRET_NAME), 'data' => $this->data()]);
        $this->keenio->addEvent(self::EVENT_NAME, $this->data());
    }
}
