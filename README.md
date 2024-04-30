# SWUOnline
Star Wars Unlimited Sim

## Docker environment
### Configuration
Create a .env file in the root directory with the contents of the .env.dist file.
And set the values of the variables.

DOCKER_USER with the result of the commands 
userId:
```bash id -u```
groupId:
```bash id -g```

DOCKER_USER=$userId:$groupId

### Starting environment

Run the following command to start the environment
```bash docker compose up -d```

### Stopping environment
run the following command to stop the environment
```bash docker compose down```