# SWUOnline
Star Wars Unlimited Sim

## Contact info
If you need to contact us or would like to get involved in the dev work, please reach out in our [Discord server](https://discord.gg/hKRaqHND4v)!

## Dev Quickstart

We have two options for setting up a development environment: a docker-based method that is fully automatic and supports live debug, or a walkthrough for how to set up the environment manually on your local machine.

### Dev Environment Option 1: Docker environment with live debugging
We provide a docker environment for local run with built-in xdebug support for live debugging, which can be installed and started in two commands if you already have docker set up. A preconfigured setup is provided for Visual Studio Code (it would be very simple to set up other tools as well).

#### Step 0. Install docker
If you are on Windows, please follow the instructions for installing Docker Desktop on Windows (either WSL or Hyper-V backend should work but we have only tested with WSL): https://docs.docker.com/desktop/install/windows-install/

#### Step 1. User Configuration (optional in Docker for Windows)
_(Docker Desktop for Windows will handle user permissions automatically without this step.)_

Create a .env file in the root directory with the contents of the .env.dist file.
And set the value of the DOCKER_USER with the result of these commands:

```bash
bash id -u  # user id
bash id -g  # group id
```

Use the format `DOCKER_USER=$userId:$groupId`. For example, `DOCKER_USER=1000:1000`.

#### Step 2. Starting / stopping the service

Run the following commands to start / stop the service
```bash
bash docker compose up -d   # start
bash docker compose down    # stop
bash docker compose restart # restart
```

#### Step 3. Accessing the application

Open this address in your browser: http://localhost:8080/SWUOnline/MainMenu.php

If you want to play a game against yourself, open multiple windows / tabs and connect.

#### Step 4. Run debugger in VSCode (optional)
Xdebug is already running in the service, you can use these steps to do live debugging with breakpoints in Visual Studio Code:

1. Install an extension that supports PHP debugging, such as https://marketplace.visualstudio.com/items?itemName=DEVSENSE.phptools-vscode
2. We have a preconfigured [launch.json](.vscode/launch.json) to enable the debug action. In the vscode debug window (Ctrl + Shift + D), select the configuration `SWUOnline: Listen for Xdebug` and hit the Run button.
3. You are now connected for debugging, add a breakpoint and try it out :)

Additionally, any tool that can connect to xdebug remotely should work as well.

### Dev Environment Option 2: Manual Environment Setup

We have a Google Doc with instructions for setting up the environment. Some steps may be missing or require extra detail, if you find any issues please contact us via the Discord so we can improve the document.

https://docs.google.com/document/d/10u3qGpxr1ddvwobq8__lVZfYCgqtanZShHEaYljiA1M/edit?usp=sharing
