<?php
/**
 * Nextcloud - Giphy
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
 * @copyright Julien Veyssier 2022
 */

$requirements = [
	'apiVersion' => 'v1',
	//	'token' => '^[a-z0-9]{4,30}$',
];

return [
	'routes' => [
		['name' => 'config#setAdminConfig', 'url' => '/admin-config', 'verb' => 'PUT'],
		['name' => 'config#setUserConfig', 'url' => '/config', 'verb' => 'PUT'],
		// not used for now
		['name' => 'giphyAPI#getGifFromId', 'url' => '/gif/{gifId}', 'verb' => 'GET'],
		['name' => 'giphyAPI#getGifFromDirectUrl', 'url' => '/gif/direct/{gifId}/{domainPrefix}/{fileName}/{cid}/{rid}/{ct}', 'verb' => 'GET'],
		['name' => 'giphyAPI#privateGetGifFromDirectUrl', 'url' => '/private/gif/direct/{gifId}/{domainPrefix}/{fileName}/{cid}/{rid}/{ct}', 'verb' => 'GET'],
	],
	'ocs' => [
		['name' => 'giphyAPI#getTrendingGifs', 'url' => '/api/{apiVersion}/gifs/trending', 'verb' => 'GET', 'requirements' => $requirements],
		['name' => 'giphyAPI#searchGifs', 'url' => '/api/{apiVersion}/gifs/search', 'verb' => 'GET', 'requirements' => $requirements],
	],
];
