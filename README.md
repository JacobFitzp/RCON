# RCON

RCON is a protocol used by source dedicated servers, which allows console commands to be issued to the server via a "remote console".
This library allows for remote execution of game server commands via RCON using PHP 7.

Game servers that support RCON include (but are not limited to):

* Arma 3
* Garry's Mod
* 7 Days To Die
* Minecraft (Java Edition)
* Rust
* Squad
* ARK: Survival Evolved

## Installation 

Install using composer:

``` composer require jfitz/rcon ```

## Usage

```php 

# Create new socket
$socket = new \Jfitz\Rcon\Socket('127.0.0.1', 'Password123', 25565, 30);

# Check connection
if ($socket->isConnected()) {
    
    # Execute a command
    $socket->execute('/say Hello World!');
}

```

## Contribution 

I welcome contribution to all my repositories, but please note the following rules:

* Code must follow PSR coding standards
* Commit messages must follow standards (see bellow)

### Commit Messages

Any contributions must follow the commit message standard as shown bellow.

```
Summary of the change

WHAT / WHY: Description of what the change is and why its being done

HOW: Description of how this commit applies to the change

Issue number (if applicable) 
```

Example Commit:

``` 
Implement support for multi-packet responses

When the response packet exceeds 4096 characters it gets split into multiple packets, we need to add support for this so responses don't get truncated

This commit adds support for multi-packet responses by adding a loop in the execute method that will continue to read packets until the end of the response is found
```