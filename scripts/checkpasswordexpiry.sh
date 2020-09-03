#!/bin/bash
#
rcvr1=wireland@nonprofitsoftwarecorp.org

for i in  hhkapp
do

  # convert current date to seconds
  currentdate=$(date +%s)

  # find expiration date of user
  userexp=$(chage -l $i | awk -F ":" '/^Password expires/ { print $NF }')

  if [[ ! -z $userexp ]]
  then

    # convert expiration date to seconds
    passexp=$(date -d "$userexp" "+%s")

    if [[ $passexp != "never" ]]
    then
      # find the remaining days for expiry
      (( exp = passexp - currentdate))

      # convert remaining days from sec to days
      (( expday =  exp / 86400 ))

      echo $expday

      if [[ $expday < 10 ]] && [[ "$1" -eq "sendemail" ]]
      then
        echo "Don't forget to change password for $i"  | mailx -r "VM Racks Server <noreply@nonprofitsoftwarecorp.org>" -s "Password for $i will expire in $expday day/s" $rcvr1
      fi
    fi
  fi

done