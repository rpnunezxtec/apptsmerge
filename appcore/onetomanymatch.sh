#!/bin/bash
# cron based operation of the onetomanymatch program

# The program location 
prog_otmm=/authentx/app/onetomanymatch/onetomanymatch

# The configuration directives
config=/authentx/app/https/authentx/appconfig/onetomanymatch.cfg

# The switch file
# if this file is present then the system is active
otmm_enable=/var/lock/subsys/otmm.enable

if [ -f ${otmm_enable} ];
then
  ${prog_otmm} ${config}
fi

exit 0

