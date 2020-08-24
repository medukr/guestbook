<?php
/**
 * Created by andrii
 * Date: 24.08.20
 * Time: 20:04
 */

namespace App;


use App\Entity\Comment;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpamChecker
{

    private $client;
    private $endpoint;

    public function __construct(HttpClientInterface $client, string $akismet_key)
    {
        $this->client = $client;
        $this->endpoint = sprintf('https://%s.rest.akismet.com/1.1/comment-
check', $akismet_key);
    }

    /**
     * @return int Spam score: 0: not spam, 1: maybe spam, 2: blatant spam
    164*
     * @throws \RuntimeException if the call did not work
     */
    public function getSpamScore(Comment $comment, array $context): int
    {
        $responce = $this->client->request('POST', $this->endpoint, [
            'body' => array_merge($context, [
                'blog' => 'https://guestbook.example.com',
                'comment_type' => 'comment',
                'comment_author' => $comment->getAuthor(),
                'comment_author_email' => $comment->getEmail(),
                'comment_content' => $comment->getText(),
                'comment_date_gmt' => $comment->getCreateAt()->format('c'),
                'blog_lang' => 'en',
                'blog_charset' => 'UTF-8',
                'is_test' => true
            ])
        ]);

        $headers = $responce->getHeaders();
        if ('discard' === ($headers['x-akismet-pro-tip'][0] ?? ''))
            return 2;

        $content = $responce->getContent();
        if (isset($headers['x-akismet-debug-help'][0])) {
            throw new \RuntimeException(sprintf('Unable to check for spam: %s
(%s).', $content, $headers['x-akismet-debug-help'][0]));
        }

        return 'true' === $content ? 1 : 0;
    }

}