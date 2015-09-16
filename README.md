# Radiant brook

## What is Radiant brook?

A simple command line process that scans a market, calculates percentiles of the market, then places bids/asks across the spread based on these percentiles.

Additionally, historical data of your account balance can be collected using a snapshot tool and pushed to Keen.IO.

## What things can be traded?

Currently only Bitcoin.

## Why bitcoin?

Because it's possible to trade bitcoin with a relatively low balance (a few hundred $USD is enough to get started) which makes it easy to prototype things and make mistakes without losing the house.

## What markets are supported?

Only Bitstamp currently.

## What markets might be supported in the future?

Other Bitcoin markets are obvious candidates for relatively swift integrations. Kraken is high on the list of candidates, for example.

Gold/Silver markets with APIs, like [BullionVault](https://www.bullionvault.com/help/API_terms.html), for example.

Other cryptocurrency markets.

Other things that can be bought/sold relatively easily through an API, with a public order book for gauging market depth.

## What is it written in?

Symfony 2, PHP.

Also, for snapshots to work, you will need a free [Keen.IO](https://keen.io/) subscription.

## Why don't you write it in X?

Maybe, someday.

## Does it have unit tests?

Yes.

## How do I set this up for myself?

1. Find the `.env.example` file (check `src/AppBundle`) and then create a `.env` file with your own settings/API keys OR export environment variables for each.
2. Run `app/console trade:bitstamp` whenever you like, this could even be a cron job or whatever
