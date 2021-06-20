<?php

namespace Jfitz\Rcon;

/**
 * Socket wrapper class for connecting and communicating with source server.
 *
 * @author Jacob Fitzpatrick <contact@jacobfitzpatrick.co.uk>
 * @package Jfitz\Rcon
 */
class Socket
{
	/** @var false|resource Socket resource */
	private $socket;
	/** @var string Server hostname */
	private string $hostname;
	/** @var string Server password */
	private string $password;
	/** @var int Minecraft server port */
	private int $port;

	/** @var bool Is connected to server */
	private bool $connected = false;

	/** @var int Authorisation packet ID */
	public const PACKET_AUTHORIZE = 5;
	/** @var int Command packet ID */
	public const PACKET_COMMAND = 6;
	/** @var int Command end packet ID */
	public const PACKET_COMMAND_END = 7;

	/** @var int Authorise packet type */
	public const DATA_AUTHORIZE = 3;
	/** @var int Authorise response packet type */
	public const DATA_AUTHORIZE_RESPONSE = 2;
	/** @var int Execute command packet type */
	public const DATA_EXECUTE_COMMAND = 2;
	/** @var int Response packet type */
	public const DATA_RESPONSE_VALUE = 0;

	/**
	 * RCON Socket
	 *
	 * @param string $hostname Server hostname
	 * @param string $password RCON password
	 * @param int    $port Server Port
	 * @param float    $timeout Connection timeout in seconds
	 */
	public function __construct (
		string $hostname,
		string $password,
		int $port = 25565,
		float $timeout = 30.00
	) {
		/* Set parameters */
		$this->hostname = $hostname;
		$this->password = $password;
		$this->port = $port;

		/* Open socket */
		$this->socket = fsockopen(
			$this->getHostname(),
			$this->getPort(),
			$error_code,
			$error_message,
			$timeout
		);

		/* Check connection */
		if ($this->socket && $this->authorise()) {
			$this->connected = true;
		}
	}

	/**
	 * Authorise
	 *
	 * @return bool
	 */
	private function authorise () : bool
	{
		$this->writePacket(self::PACKET_AUTHORIZE, self::DATA_AUTHORIZE,
			$this->password);
		$response_packet = $this->readPacket();

		return (
			$response_packet['type'] === self::DATA_AUTHORIZE_RESPONSE
			&& $response_packet['id'] === self::PACKET_AUTHORIZE
		);
	}

	/**
	 * Write packet to the socket
	 *
	 * @param int $id Packet ID
	 * @param int $type Packet Type
	 * @param string $body Packet Body
	 */
	private function writePacket (int $id, int $type, string $body) : void
	{
		if (!$this->isConnected()) {
			return;
		}

		/* Create packet */
		$packet = pack('VV', $id, $type);
		$packet .= $body . "\x00";
		$packet .= "\x00";

		/* Get size of packet */
		$packet_size = strlen($packet);

		/* Attach size to packet */
		$packet = pack('V', $packet_size) . $packet;

		/* Write the packet */
		fwrite($this->getSocket(), $packet, strlen($packet));
	}

	/**
	 * Read response packet
	 *
	 * @return array|false
	 */
	private function readPacket ()
	{
		if (!$this->isConnected()) {
			return false;
		}

		/* Get packet size */
		$size_data = fread($this->getSocket(), 4);
		$size_pack = unpack('V1size', $size_data);
		$size = $size_pack['size'];

		/* Read packet */
		$packet_data = fread($this->getSocket(), $size);

		/* Unpack packet */
		return unpack('V1id/V1type/a*body', $packet_data);
	}

	/**
	 * Execute command.
	 * This is the command you want to run on the server.
	 *
	 * @param string $command Command to run
	 *
	 * @return false|string Response string, false if there was no response or connection is not open
	 */
	public function execute (string $command)
	{
		/* Return false if we are not authorised */
		if (!$this->isConnected()) {
			return false;
		}

		/* Send command packet */
		$this->writePacket(self::PACKET_COMMAND, self::DATA_EXECUTE_COMMAND, $command);

		/* Send ping packet so we can determine the end of the response later */
		$this->writePacket(self::PACKET_COMMAND_END, self::DATA_EXECUTE_COMMAND, 'ping');

		/* Get response as string */
		$response = '';
		$response_packet = $this->readPacket();

		/* Large responses are split into multiple packets, we need to loop through the entire response */
		while (
			$response_packet['id'] === self::PACKET_COMMAND
			&& $response_packet['type'] === self::DATA_RESPONSE_VALUE
		) {
			$response .= $response_packet['body'];
			$response_packet = $this->readPacket();
		}

		$response = substr($response, 0, - 3);

		/* Return response if its not empty */
		if ($response !== '') {
			return $response;
		}

		/* There is no response, something might have gone wrong */
		return false;
	}

	/**
	 * Get socket resource
	 *
	 * @return false|resource
	 */
	public function getSocket ()
	{
		return $this->socket;
	}

	/**
	 * Get server hostname
	 *
	 * @return string
	 */
	public function getHostname () : string
	{
		return $this->hostname;
	}

	/**
	 * Get server port
	 *
	 * @return int
	 */
	public function getPort () : int
	{
		return $this->port;
	}

	/**
	 * Is connected to server
	 *
	 * @return bool
	 */
	public function isConnected () : bool
	{
		return $this->connected;
	}
}