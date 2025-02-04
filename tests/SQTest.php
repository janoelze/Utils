<?php

use JanOelze\Utils\SQ;
use PHPUnit\Framework\TestCase;

class SQTest extends TestCase
{
  public function testBasic()
  {
    // We use an in-memory SQLite DB for testing (will be recreated fresh each time).
    $sq = new SQ(['db' => ':memory:']);

    // 1) Create a 'user' record and save it
    $record = $sq->dispense('user');
    $record->name  = 'Alice';
    $record->email = 'alice@example.com';
    $record->save();

    // Update assertions to confirm $record is instance of SQRecord
    $this->assertInstanceOf(SQRecord::class, $record);

    // Assert that an ID, UUID, created_at, and updated_at were assigned
    $this->assertNotEmpty($record->id, 'User ID should be assigned after save.');
    $this->assertNotEmpty($record->uuid, 'User UUID should be assigned after save.');
    $this->assertNotEmpty($record->created_at, 'User created_at should be set.');
    $this->assertNotEmpty($record->updated_at, 'User updated_at should be set.');

    // 2) Fetch the user from DB by criteria
    $foundUsers = $sq->find('user', ['name' => 'Alice']);
    $this->assertCount(1, $foundUsers, 'Should find exactly one user named Alice.');
    $foundUser = $foundUsers[0];
    $this->assertEquals('alice@example.com', $foundUser->email, 'Fetched user should have the correct email.');

    // 3) Update the user
    $foundUser->email = 'alice_new@example.com';
    $foundUser->save();

    // 4) Fetch again to check if update worked
    $updatedUser = $sq->findOne('user', ['id' => $foundUser->id]);
    $this->assertEquals('alice_new@example.com', $updatedUser->email, 'User email should be updated.');
    $this->assertNotEmpty($updatedUser->updated_at, 'User updated_at should be updated.');

    // 5) Create a 'post' record linked to the user
    $post = $sq->dispense('post');
    $post->title   = 'My First Post';
    $post->content = 'Hello from my first post!';
    $post->user_id = $foundUser->id;
    $post->save();

    $this->assertNotEmpty($post->id, 'Post ID should be assigned after save.');
    $this->assertNotEmpty($post->uuid, 'Post UUID should be assigned after save.');
    $this->assertNotEmpty($post->created_at, 'Post created_at should be set.');
    $this->assertNotEmpty($post->updated_at, 'Post updated_at should be set.');

    // 6) Verify the post can be retrieved via query builder
    $posts = $sq->query('post')
      ->where('user_id', '=', $foundUser->id)
      ->get();
    $this->assertCount(1, $posts, 'Should find exactly one post for this user.');
    $this->assertEquals('My First Post', $posts[0]->title);

    // 7) Delete the user and verify they're gone
    $foundUser->delete();
    $stillFound = $sq->find('user', ['id' => $foundUser->id]);
    $this->assertEmpty($stillFound, 'User should be deleted and not found anymore.');

    // 8) (Optional) Verify the post is still there unless you want to cascade,
    // but by default, we only tested user deletion, so the post remains.
    $remainingPosts = $sq->find('post', ['id' => $post->id]);
    $this->assertNotEmpty($remainingPosts, 'Post should remain after user deletion (no cascade).');
  }
}
