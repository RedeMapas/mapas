<?php
namespace MapasCulturaisTests;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class FilesTest extends TestCase
{

    function testThumbnailCreationAPI()
    {
        $this->markTestSkipped();
        $this->assertGet200('/api/agent/find?@select=id,name&@files=(avatar.galleryFull):url', 'assert that thumbnail is created without error (/api/agent/find?@select=id,name&@files=(avatar.galleryFull):url)');
    }
}