# SWUOnline
Star Wars Unlimited Sim

## Docker environment with live debugging
We provide a docker environment for local run with built-in xdebug support for live debugging. A preconfigured setup is provided for Visual Studio Code (it would be very simple to set up other tools as well).

### Step 1. Configuration (optional in Docker for Windows)
_(Docker Desktop for Windows will handle user permissions automatically without this step.)_

Create a .env file in the root directory with the contents of the .env.dist file.
And set the value of the DOCKER_USER with the result of these commands:

```bash
bash id -u  # user id
bash id -g  # group id
```

Use the format `DOCKER_USER=$userId:$groupId`. For example, `DOCKER_USER=1000:1000`.

### 2. Starting / stopping the service

Run the following commands to start / stop the service
```bash
bash docker compose up -d   # start
bash docker compose down    # stop
bash docker compose restart # restart
```

### 3. Accessing the application

Open this address in your browser: http://localhost:8080/SWUOnline/MainMenu.php

If you want to play a game against yourself, open multiple windows / tabs and connect.

### 4. Run debugger in VSCode (optional)
Xdebug is already running in the service, you can use these steps to do live debugging with breakpoints in Visual Studio Code:

1. Install an extension that supports PHP debugging, such as https://marketplace.visualstudio.com/items?itemName=DEVSENSE.phptools-vscode
2. We have a preconfigured [launch.json](.vscode/launch.json) to enable the debug action. In the vscode debug window (Ctrl + Shift + D), select the configuration `SWUOnline: Listen for Xdebug` and hit the Run button.
3. You are now connected for debugging, add a breakpoint and try it out :)

Additionally, any tool that can connect to xdebug remotely should work as well.