#!/bin/sh
curl -w@"curl-format.txt" -XPOST -d@"PushEvent.json" http://localhost:8080/gitlab/action
