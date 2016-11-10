#!/bin/sh
curl -w@"curl-format.txt" -XPOST -d@"IssueEvent.json" http://zephyr.dev/gitlab/action
