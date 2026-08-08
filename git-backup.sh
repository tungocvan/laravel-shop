#!/bin/bash
rm -rf storage/app/livewire-tmp/*
param1="$1"
if [ -z "$param1" ]; then
  echo "Vui long nhap ten de commit"
  exit 1
fi

git add . 
git commit -m"$param1"
git push
