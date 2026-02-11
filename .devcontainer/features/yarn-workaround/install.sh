#!/usr/bin/env bash
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor | sudo tee /usr/share/keyrings/yarn-archive-keyring.gpg > /dev/null
