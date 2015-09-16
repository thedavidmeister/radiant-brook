# Radiant brook

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/thedavidmeister/radiant-brook/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/thedavidmeister/radiant-brook/?branch=master) [![Build Status](https://scrutinizer-ci.com/g/thedavidmeister/radiant-brook/badges/build.png?b=master)](https://scrutinizer-ci.com/g/thedavidmeister/radiant-brook/build-status/master)

## What is Radiant brook?

A simple command line process that scans a market, calculates percentiles of the market, then places bids/asks across the spread based on these percentiles.

Duplicates trades are detected, within a configurable tolerance, and will not be placed.

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

## Is this a high frequency trading bot?

No, for a few reasons:

1. The bot has no concept of frequency, it looks for and places trades once per run. Scheduling runs has to be handled by some external process, like cron jobs in the OS, for example.
2. It relies on consuming RESTful APIs, which will generally be too slow to compete with realtime streaming APIs, such as websockets.
3. The algorithm this bot uses sees diminishing returns from increasing the frequency of market scans for sane (profitable) configurations.

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
