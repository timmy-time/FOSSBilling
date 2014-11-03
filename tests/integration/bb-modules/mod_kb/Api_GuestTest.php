<?php
/**
 * @group Core
 */
class Api_Guest_KbTest extends BBDbApiTestCase
{
    protected $_initialSeedFile = 'news.xml';
    
    public function testKb()
    {
        $array = $this->api_guest->kb_article_get_list();
        $this->assertInternalType('array', $array);

        $array = $this->api_guest->kb_category_get_list();
        $this->assertInternalType('array', $array);

        $data = array(
            'id'    =>  1,
        );
        $array = $this->api_guest->kb_article_get($data);
        $this->assertInternalType('array', $array);

        $data = array(
            'slug'    =>  'how-to-contact-support',
        );
        $array = $this->api_guest->kb_article_get($data);
        $this->assertInternalType('array', $array);

        $data = array(
            'slug'    =>  'discuss-about-everything',
        );
        $array = $this->api_guest->kb_category_get($data);
        $this->assertInternalType('array', $array);
    }

    public function testcategory_get_pairs()
    {
        $array = $this->api_guest->kb_category_get_pairs();
        $this->assertInternalType('array', $array);
    }

    public function testArticleGetList()
    {
        $array = $this->api_guest->kb_article_get_list();
        $this->assertInternalType('array', $array);

        $this->assertArrayHasKey('list', $array);
        $list = $array['list'];
        $this->assertInternalType('array', $list);

        if (count($list)) {
            $item = $list[0];
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('slug', $item);
            $this->assertArrayHasKey('title', $item);
            $this->assertArrayHasKey('views', $item);
            $this->assertArrayHasKey('created_at', $item);
            $this->assertArrayHasKey('updated_at', $item);
            $this->assertArrayHasKey('category', $item);

            $category = $item['category'];
            $this->assertInternalType('array', $category);
            $this->assertArrayHasKey('id', $category);
            $this->assertArrayHasKey('slug', $category);
            $this->assertArrayHasKey('title', $category);
        }
    }

    public function testCategoryGetList()
    {
        $array = $this->api_admin->kb_category_get_list();
        $this->assertInternalType('array', $array);

        $this->assertArrayHasKey('list', $array);
        $list = $array['list'];
        $this->assertInternalType('array', $list);

        if (count($list)) {
            $item = $list[0];
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('title', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('slug', $item);
            $this->assertArrayHasKey('created_at', $item);
            $this->assertArrayHasKey('updated_at', $item);
            $this->assertArrayHasKey('articles', $item);

            $articles = $item['articles'];
            if (count($articles)) {
                $article = $articles[0];
                $this->assertInternalType('array', $article);
                $this->assertArrayHasKey('id', $article);
                $this->assertArrayHasKey('slug', $article);
                $this->assertArrayHasKey('title', $article);
            }

        }
    }

}