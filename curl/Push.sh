#!/bin/sh
curl -w@"curl-format.txt" -XPOST -d@"PushEvent.json" http://zephyr.dev/gitlab/action
