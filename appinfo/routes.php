<?php
/**
 * Nextcloud - Giphy
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2022
 */

return [
	'routes' => [
		['name' => 'config#setAdminConfig', 'url' => '/admin-config', 'verb' => 'PUT'],
		['name' => 'giphyAPI#getGifFromId', 'url' => '/gif/{gifId}', 'verb' => 'GET'],
		['name' => 'giphyAPI#getGifFromDirectUrl', 'url' => '/gif/direct/{gifId}/{domainPrefix}/{fileName}/{cid}/{rid}/{ct}', 'verb' => 'GET'],
	]
];
