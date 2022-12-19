<?php

namespace OCA\Giphy\Tests;


use OCA\Giphy\AppInfo\Application;
use OCA\Giphy\Service\GiphyAPIService;

class GiphyAPIServiceTest extends \PHPUnit\Framework\TestCase {

	public function testDummy() {
		$app = new Application();
		$this->assertEquals('integration_giphy', $app::APP_ID);
	}

	public function testPaginationConversion() {
		$expecteds = [
			// mediaUrl => domainPrefix, fileName, cid, rid, ct
			[
				'https://media4.giphy.com/media/BaDsH4FpMBnqdK8J0g/giphy.gif?cid=ae23904804a21bf61bc9d904e66605c31a584d73c05db5ad&rid=giphy.gif&ct=g',
				['media4', 'giphy.gif', 'ae23904804a21bf61bc9d904e66605c31a584d73c05db5ad', 'giphy.gif', 'g'],
			],
		];

		foreach ($expecteds as $expected) {
			$mediaUrl = $expected[0];
			$expected = $expected[1];
			$result = GiphyAPIService::getGifUrlInfo($mediaUrl);
			$this->assertEquals($expected, $result);
		}
	}
}
