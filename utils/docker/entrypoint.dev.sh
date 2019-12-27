#!/bin/bash

# Start the server and dev watcher
(
    apache2-foreground & su node -c "npm run dev"
)
