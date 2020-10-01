# Advanced Custom Fields: Network posts select field

![GitHub release (latest SemVer)](https://img.shields.io/github/v/release/timiwahalahti/acf-field-network-post-select?style=flat-square) ![Tested_up_to WordPress_5.5](https://img.shields.io/badge/tested_up_to-WordPress_5.5-blue.svg?style=flat-square) ![Compatible_with AFC_5](https://img.shields.io/badge/compatible_with-ACF_5-green.svg?style=flat-square) ![GitHub](https://img.shields.io/github/license/timiwahalahti/acf-field-network-post-select?style=flat-square)

**Adds a ACF field that allows selecting posts across the network sites.**

![Screenshot 2020-10-01 at 21.43.59](https://i.imgur.com/D5HZxQW.png)

The field is very much silimar to native ACF [Post Object](https://www.advancedcustomfields.com/resources/post-object/) one, with the exception that network posts select field will allow users to search posts across the entire network rather than just the site in which dashboard they are. On field settings it's possible to confugure a list of allowed sites in network from which the posts can be selected.

Because you don't do much with post ID or object if it's for wrong site in a network, the field returns always an array that contains `site_id` and `post_id`. Then you, as a developer, can use these ID's however you like.

![Screenshot 2020-10-01 at 21.42.06](https://i.imgur.com/zPDpsvk.png)

### Note about large networks

It's highly suggested to use site filter if your network has tens of sites.

Field works, but search gets slower as the number of sites in network increases. This is something I might try to enhance in future. If your network has over 100 sites, field will try to get posts only from the first 100 sites. To change this, use `acf/fields/network_post_select/settings/max_sites` filter to tell how many sites are shown in field options.

In case you like to live dangerously, there's also `acf/fields/network_post_select/result` filter available where you can change arguments for `get_sites` function used in posts search.

## Changelog

Changelog can be found from [releases page](https://github.com/timiwahalahti/acf-field-network-post-select/releases) and separate [CHANGELOG.md](https://github.com/timiwahalahti/acf-field-network-post-select/tree/main/CHANGELOG.md) file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Contributing

Code contributions, feedback, and feature suggestions are very welcome.
