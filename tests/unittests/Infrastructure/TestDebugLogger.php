<?php

namespace unittests\Infrastructure;

use Psr\Log\LoggerInterface;

if (!class_exists("TestDebugLogger")) {
	class TestDebugLogger implements LoggerInterface {
		public function __construct() {
			$this->debug("GdataAntivirusPluginDebugLogger::__construct");
        }

		public function get_caller() {
			$callers = debug_backtrace();
			$classname = explode('\\', $callers[2]["class"]);
			return end($classname) . "::" . $callers[2]["function"];
		}

		public function emergency( string|\Stringable $message, array $context = array() ): void {
			$caller = $this->get_caller();
			fwrite(\STDERR, "[emergency][$caller] ".$message."\n");
        }

		public function alert( string|\Stringable $message, array $context = array() ): void {
			$caller = $this->get_caller();
			fwrite(\STDERR, "[alert][$caller] ".$message."\n");
		}

		public function critical( string|\Stringable $message, array $context = array() ): void {
			$caller = $this->get_caller();
			fwrite(\STDERR, "[critical][$caller] ".$message."\n");
		}

		public function error( string|\Stringable $message, array $context = array() ): void {
			$caller = $this->get_caller();
			fwrite(\STDERR, "[error][$caller] ".$message."\n");
		}

		public function warning( string|\Stringable $message, array $context = array() ): void {
			$caller = $this->get_caller();
			fwrite(\STDERR, "[warning][$caller] ".$message."\n");
		}

		public function notice( string|\Stringable $message, array $context = array() ): void {
			$caller = $this->get_caller();
			fwrite(\STDERR, "[notice][$caller] ".$message."\n");
		}

		public function info( string|\Stringable $message, array $context = array() ): void {
			$caller = $this->get_caller();
			fwrite(\STDERR, "[info][$caller] ".$message."\n");
		}

		public function debug( string|\Stringable $message, array $context = array() ): void {
			$caller = $this->get_caller();
			fwrite(\STDERR, "[debug][$caller] ".$message."\n");
		}

		public function log( $level, string|\Stringable $message, array $context = array() ): void {
			$caller = $this->get_caller();
			fwrite(\STDERR, "[$level][$caller] ".$message."\n");
		}
	}
}
