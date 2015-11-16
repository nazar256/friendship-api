<?php

/**
 * MongoDB document repository
 * PHP version 5.6
 * @category Class
 * @package  AppBundle
 * @author   nazar <jura_n@bk.ru>
 * @license  MIT @link https://opensource.org/licenses/MIT
 * @link     http://friendship-api.dev
 */

namespace AppBundle\DocumentRepository;

use AppBundle\Helper\ArrayHelper;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * MongoDB User document repository
 * @category Repository
 * @package  AppBundle
 * @author   nazar <jura_n@bk.ru>
 * @license  MIT @link https://opensource.org/licenses/MIT
 * @link     localhost
 */
class UserRepository extends DocumentRepository
{
    /**
     * Fetches friends of friends on N-th nesting level
     * @param string $userId       user ID of whose friends initially are requested
     * @param int    $nestingLevel level of nesting
     * @return array
     */
    public function findNestedFriends($userId, $nestingLevel)
    {
        $currentUsersToQuery = [$userId];
        $totalFriends = [];
        // Iterate through nesting levels
        for ($currentLevel = 0; $currentLevel <= $nestingLevel; $currentLevel++) {
            $result = $this
                ->createQueryBuilder()
                ->hydrate(false)
                ->select('_friends')
                ->field('_id')
                ->in($currentUsersToQuery)
                ->getQuery()
                ->execute()
                ->toArray();

            // Get all new found friends list
            $currentFriends = [];
            foreach ($result as $user) {
                $currentFriends = ArrayHelper::sumSets(
                    $currentFriends,
                    $user['_friends']
                );
            }
            // Query next time for found friends, but excluding previously found ones
            $currentUsersToQuery = array_diff($currentFriends, $totalFriends);
            $totalFriends = ArrayHelper::sumSets($totalFriends, $currentFriends);
        }

        return array_diff($totalFriends, [$userId]);    // Do not take current user
    }
}