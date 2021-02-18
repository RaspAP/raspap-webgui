![](https://i.imgur.com/DpgvLIO.png)
[![Release 2.7](https://img.shields.io/badge/release-v2.7-green)](https://github.com/raspap/raspap-insiders/releases) [![Awesome](https://awesome.re/badge.svg)](https://github.com/thibmaek/awesome-raspberry-pi) [![Financial Contributors on Open Collective](https://opencollective.com/raspap/all/badge.svg?label=financial+contributors)](https://opencollective.com/raspap) ![https://travis-ci.com/github/raspap/raspap-webgui/](https://api.travis-ci.org/RaspAP/raspap-webgui.svg) [![Crowdin](https://badges.crowdin.net/raspap/localized.svg)](https://crowdin.com/project/raspap) [![Twitter URL](https://img.shields.io/twitter/url?label=%40RaspAP&logoColor=%23d8224c&url=https%3A%2F%2Ftwitter.com%2Frasp_ap)](https://twitter.com/rasp_ap) [![Subreddit subscribers](https://img.shields.io/reddit/subreddit-subscribers/RaspAP?style=social)](https://www.reddit.com/r/RaspAP/)

Welcome to **RaspAP Insiders**. You, the members of the Insiders community, support the sponsorware release model, which means that new features are first exclusively released to sponsors as part of Insiders. Read on for details about how this strategy works—and *thank you* for joining us on this journey.

## Contents

 - [How sponsorship works](#how-sponsorship-works)
 - [About your sponsorship](#about-your-sponsorship)
 - [Exclusive features](#exclusive-features)
 - [Funding targets](#funding-targets)
 - [Frequently asked questions](#frequently-asked-questions)

## How sponsorship works
New features first land in Insiders, which means that *sponsors will have access to them immediately*. Every feature is tied to a funding goal in monthly subscriptions. When a funding goal is hit, the features that are tied to it are merged back into the [public RaspAP repository](https://github.com/RaspAP/raspap-webgui) and released for general availability. Bugfixes and minor enhancements are always released simultaneously in both editions.

Don't want to sponsor? No problem, RaspAP already has tons of features available, so chances are that most of your requirements are already satisfied. See the list of [exclusive features](#exclusive-features) to learn which features are currently only available to sponsors.

## About your sponsorship
Your sponsorship is through your individual or organization's GitHub account. By visiting [**RaspAP's sponsor profile**](https://github.com/sponsors/RaspAP), you may change your sponsorship tier or cancel your sponsorship anytime. <sup>[1](#footnote-1)</sup>

As part of the initial rollout of Insiders, all previous one-time backers of RaspAP on GitHub, PayPal or [Open Collective](https://opencollective.com/raspap) will receive unlimited access to Insiders. This is a small gesture for those early financial supporters who recognized the potential of this project.


> ℹ️ **Important**: If you're [sponsoring](https://github.com/sponsors/RaspAP) RaspAP through a GitHub organization, please send a short email to [sponsors@raspap.com](mailto:sponsors@raspap.com) with the name of your organization and the account that should be added as a collaborator. <sup>[2](#footnote-2)</sup>

## Exclusive features
When backers were asked which feature they'd most like to see added to RaspAP, the ability to manage multiple OpenVPN client configurations topped the list of requests. Therefore, we're adding this as the first feature exclusive to insiders. 

✅ Manage OpenVPN client configs  
✅ OpenVPN service logging  
✅ Night mode toggle  
⚙️ Traffic shaping (in progress)  

Look for the list above to grow as we add more exlcusive features. Have an idea or suggestion for a future enhancement? Start or join an [Insiders discussion](https://github.com/orgs/RaspAP/teams/insiders/discussions) and let us know!

## Funding targets
Following is a list of funding targets. When a funding target is reached, the features that are tied to it are merged back into RaspAP and released to the public for general availability.

### $500 
✅ Manage OpenVPN client configs  
✅ OpenVPN service logging  
✅ Night mode toggle  
⚙️ Traffic shaping (in progress)  

## Frequently asked questions

### Upgrading
*I have an existing RaspAP installation. How do I upgrade to Insiders?*

Upgrading is easy. Simply invoke the Quick Installer with the `--upgrade` switch, specifying the private Insiders repo, like so:

```
curl -sL https://install.raspap.com | bash -s -- --upgrade --repo raspap/raspap-insiders
```

If you haven't [added SSH keys to your GitHub account](https://docs.github.com/en/github/authenticating-to-github/connecting-to-github-with-ssh) you will be prompted to authenticate. If so, just enter your GitHub credentials during the install:

```
RaspAP Install: Cloning latest files from github
Cloning into '/tmp/raspap-webgui'...
Username for 'https://github.com': octocat
Password for 'https://octocat@github.com':
```

> ℹ️  Note: your password is sent securely via SSH to GitHub. The above prompt is actually from GitHub, so the installer does not know your credentials.

### Terms
*We're using RaspAP for a commercial project. Can we use Insiders under the same terms and conditions?*

Yes. Whether you're an individual or a company, you may use RaspAP Insiders precisely under the same terms as RaspAP, which are defined by the [GNU GPL-3.0 license](https://github.com/RaspAP/raspap-insiders/blob/master/LICENSE). However, we kindly ask you to respect the following guidelines:

* Please **don't distribute the source code** of Insiders. You may freely use it for public, private or commercial projects, fork it, mirror it, do whatever you want with it, but please don't release the source code, as it would counteract the sponsorware strategy.
* If you cancel your subscription, you're removed as a collaborator and will miss out on future updates of Insiders. However, you may *use the latest version* that's available to you as long as you like. Just remember that [GitHub deletes private forks](https://docs.github.com/en/github/setting-up-and-managing-your-github-user-account/removing-a-collaborator-from-a-personal-repository).

## License
See the [LICENSE](./LICENSE) file.

### Footnotes

<sub><a name="footnote-1"></a>1. If you cancel your sponsorship, GitHub schedules a cancellation request which will become effective at the end of the billing cycle, which ends at the 22nd of a month for monthly sponsorships. This means that even though you cancel your sponsorship, you will keep your access to Insiders as long as your cancellation isn't effective. All charges are processed by GitHub through Stripe. As we don't receive any information regarding your payment, and GitHub doesn't offer refunds, sponsorships are non-refundable.</sub>

<sub><a name="footnote-2"></a>2. It's currently not possible to grant access to each member of an organization, as GitHub only allows for adding users. Thus, after sponsoring, please send an email to sponsors@raspap.com, stating which account should become a collaborator of the Insiders repository. We're working on a solution which will make access to organizations much simpler.</sub>
