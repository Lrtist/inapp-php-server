#!/bin/bash
nohup php -S localhost:3001 index.php > server.log 2>&1 &
echo "Server started in background, PID: $!"
