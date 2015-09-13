<?php

namespace AppBundle\API\Bitstamp\TradePairs;

/**
 * Interface for a single TradeProposal.
 *
 * TradeProposals must be able to be validated/invalidated, enforced and request
 * that further processing halt.
 */
interface TradeProposalInterface
{
    /**
     * The reasons for the current proposal.
     *
     * @return array
     *   The reasons given that led to the current state of the proposal, in
     *   chronological order.
     */
    public function reasons();

    /**
     * Is this TradeProposal valid?
     *
     * Valid trade proposals are eligible to be actioned as a BuySell on the
     * live market.
     *
     * Throws an exception if validity has not been established.
     *
     * @see invalidate()
     * @see validate()
     *
     * @return bool
     *   True if the proposal is valid, false otherwise.
     */
    public function isValid();

    /**
     * Sets the TradeProposal as valid, if not previously invalidated.
     *
     * There is no reason for validate().
     *
     * @see invalidate()
     * @see isValid()
     *
     * @return bool
     *   Returns the result of isValid().
     */
    public function validate();

    /**
     * Sets the TradeProposal as invalid.
     *
     * Invalid trade proposals MUST NOT be actioned as a BuySell on the live
     * market.
     *
     * Throws an exception if a reason is not given.
     *
     * @param string $reason
     *   The reason for the invalidation.
     *
     * @see validate()
     * @see isValid()
     *
     * @return bool
     *   Returns the result of isValid().
     */
    public function invalidate($reason);

    /**
     * Is this TradeProposal compulsory?
     *
     * Compulsory trade proposals MUST be actioned if discovered. This implies
     * that compulsory trade proposals MUST also be final, but final proposals
     * are not necessarily compulsory.
     *
     * Defaults to false.
     *
     * Throws an exception if validity has not been established.
     *
     * @see isFinal()
     * @see isValid()
     *
     * @return bool
     *   True if this proposal is compulsory, false otherwise.
     */
    public function isCompulsory();


    /**
     * Marks a trade proposal as compulsory.
     *
     * Once a trade proposal is marked compulsory it cannot be subsequently made
     * optional.
     *
     * Throws an exception if a reason is not given.
     *
     * @see isCompulsory()
     *
     * @param string $reason
     *   The reason for ensuring compulsory status.
     *
     * @return bool
     *   Returns the value of isCompulsory (which will be true).
     */
    public function ensureCompulsory($reason);

    /**
     * Is this TradeProposal final?
     *
     * Final trade proposals, once discovered, MUST prevent further discovery of
     * subsequent trade proposals.
     *
     * Defaults to false.
     *
     * Throws an exception if validity has not been established.
     *
     * @see isCompulsory()
     * @see isValid()
     * 
     * @return boolean
     */
    public function isFinal();

    /**
     * Marks a trade proposal as final.
     *
     * Once a trade proposal is marked final it cannot subsequently be returned
     * to a sequence.
     *
     * Throws an exception if a reason is not given.
     *
     * @see isFinal()
     *
     * @param string $reason
     *   The reason for ensuring final status.
     *
     * @return bool
     *   Returns the value of isFinal (which will be true).
     */
    public function ensureFinal($reason);
}
