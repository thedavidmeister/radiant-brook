# Radiant brook

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/thedavidmeister/radiant-brook/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/thedavidmeister/radiant-brook/?branch=master) [![Build Status](https://scrutinizer-ci.com/g/thedavidmeister/radiant-brook/badges/build.png?b=master)](https://scrutinizer-ci.com/g/thedavidmeister/radiant-brook/build-status/master) [![codecov.io](http://codecov.io/github/thedavidmeister/radiant-brook/coverage.svg?branch=master)](http://codecov.io/github/thedavidmeister/radiant-brook?branch=master)
![codecov.io](http://codecov.io/github/thedavidmeister/radiant-brook/branch.svg?branch=master)

## What is Radiant brook?

A simple command line process that scans a market, calculates configurable [percentiles](http://en.wikipedia.org/wiki/Percentile) of the market depth, then places bids/asks across the spread based on these percentiles.

The minimum and maximum percentiles for a spread, and a step size, are configurable. The bot will loop from the minimum to the maximum percentile in increments of the step size and place a trade pair at the first spread found that meets the minimum USD and BTC profit requirements (also configurable).

Duplicates trades are detected, within a configurable tolerance, and will not be placed.

Additionally, historical data of your account balance can be collected using a snapshot tool and pushed to [Keen.IO](https://keen.io/).

## What things can be traded?

Currently only bitcoin.

## Why bitcoin?

Because it's possible to trade bitcoin with a relatively low balance (a few hundred $USD is enough to get started) which makes it easy to prototype things and make mistakes without losing the house.

## What markets are supported?

Only [Bitstamp](https://www.bitstamp.net/) currently.

## What markets might be supported in the future?

Other bitcoin markets are obvious candidates for relatively swift integrations. Kraken is high on the list of candidates, for example.

Gold/Silver markets with APIs, like [BullionVault](https://www.bullionvault.com/help/API_terms.html), for example.

Other cryptocurrency markets.

Other things that can be bought/sold relatively easily through an API, with a public order book for gauging market depth.

## Is this a high frequency trading bot?

No, for a few reasons:

1. The bot has no concept of frequency, it looks for and places trades once per run. Scheduling runs has to be handled by some external process, like cron jobs in the OS, for example.
2. It relies on consuming RESTful APIs, which will generally be too slow to compete with realtime streaming APIs, such as websockets.
3. The algorithm this bot uses sees diminishing returns from increasing the frequency of market scans for sane (profitable) configurations.

## What is it written in?

Symfony 2, PHP. There are no plans to maintain backwards compatibility for PHP versions - I am fully planning to jump to PHP7 and not look back (although I will bump the major version number of the repo when I do this).

Also, for snapshots to work, you will need a free [Keen.IO](https://keen.io/) subscription.

Thanks to the `HTTP_POXY` [security vulnerability in PHP](https://httpoxy.org/), it is not recommended to use versions of PHP lower than:

- 5.5.38
- 5.6.24
- 7.0.9

## Why don't you write it in X?

Maybe, someday. I mean, it would be a lot of work, so there would need to be a very good reason.

## Why did you make this?

Because YOLO.

## Does it have unit tests?

Yes.

## How do I set this up for myself?

1. Find the `.env.example` file (check `src/AppBundle`) and then create a `.env` file with your own settings/API keys OR export environment variables for each.
2. Run `app/console trade:bitstamp` whenever you like, this could even be a cron job or whatevs.

## What is the license?

MIT license, same as Symfony 2. See License.md.

Specifically, it is worth noting that MIT contains the following:

````
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
````

If it is not clear to you what this means, please consult a legal professional.
