#!/bin/bash
set -e

crontab /etc/cron.d/app-cron

cron -f -l 2
