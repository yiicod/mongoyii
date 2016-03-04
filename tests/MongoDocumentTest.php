<?php

require_once 'bootstrap.php';

class MongoDocumentTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function setUpRelationalModel()
    {
        $parentDocs = [
            ['username' => 'sam', 'job_title' => 'awesome guy'],
            ['username' => 'john', 'job_title' => 'co-awesome guy'],
            ['username' => 'dan', 'job_title' => 'programmer'],
            ['username' => 'lewis', 'job_title' => 'programmer'],
            ['username' => 'ant', 'job_title' => 'programmer']
        ];

        $childDocs = [
            ['name' => 'jogging'],
            ['name' => 'computers'],
            ['name' => 'biking'],
            ['name' => 'drinking'],
            ['name' => 'partying'],
            ['name' => 'cars']
        ];

        $docsLinkedByDBRef = [
            ['name' => 'python'],
            ['name' => 'java'],
            ['name' => 'php'],
            ['name' => 'lisp'],
            ['name' => 'C'],
            ['name' => 'Objective C'],
            ['name' => 'C++'],
            ['name' => 'ruby'],
        ];

        foreach ([['class' => 'Interest', 'data' => $childDocs], ['class' => 'Skill', 'data' => $docsLinkedByDBRef]] as $subgroup) {
            foreach ($subgroup['data'] as $doc) {
                $i = new $subgroup['class']();
                foreach ($doc as $k => $v) {
                    $i->$k = $v;
                }
                $this->assertTrue($i->save());
            }
        }

        // Lets make sure those child docs actually went in
        $skills = Skill::model()->find();
        $this->assertTrue(count($skills) > 0);
        $c = Interest::model()->count();
        $this->assertTrue($c > 0);

        // Let's build an array of the all the _ids of the child docs
        $c = Interest::model()->find();
       
        $interest_ids = [];
        foreach ($c as $row) {
            $interest_ids[] = $row->_id;
        }

        // Create the users with each doc having the value of the interest ids
        $user_ids = [];
        foreach ($parentDocs as $doc) {
            $u = new User();
            foreach ($doc as $k => $v) {
                $u->$k = $v;
            }
            $u->interests = $interest_ids;

            //Let`s take random skill as primary for this user
            $primarySkill = $skills[array_rand($skills)]->_id;
            $u->mainSkill = EMongoDBRef::create(Skill::model()->collectionName(), $primarySkill);

            //Now, lets pick array of secondary skills
            $allSecondarySkills = [];
            foreach ($skills as $i => $secondarySkill) {
                if ($secondarySkill->_id == $primarySkill) {
                    continue;
                }
                array_push($allSecondarySkills, EMongoDBRef::create(Skill::model()->collectionName(), $secondarySkill->_id));
            }

            $u->otherSkills = $allSecondarySkills;
            $this->assertTrue($u->save());
            $user_ids[] = $u->_id;
        }

        $interests = $c;
         
        // Now 50^6 times re-insert each interest with a parnt user _id
        // So we have two forms of the document in interests, one without the parent user and one with
        for ($i = 0; $i < 50; $i++) {
            $randInt = rand(0, sizeof($interests) - 1);

            $row = $interests[$randInt];            
            $randPos = rand(0, sizeof($user_ids) - 1);
            $row->i_id = $user_ids[$randPos];
            $row->setIsNewRecord(true);
            $row->setScenario('insert');
            
            $this->assertTrue($row->save());
        }
        // we will assume the set up was successful and we will leave it to further testing to see
        // whether it really was.
    }

    public function setUpProperSubdocumentErrorsIndexationTest()
    {
        return [
            [
                [
                    'username' => 'kate',
                    'addresses' => [
                        0 => [
                            'country' => 'Ukraine',
                            'telephone' => 11111
                        ],
                        1 => [
                            'country' => 'Russia',
                            'telephone' => 'wrongString'
                        ]
                    ]
                ]
            ]
        ];
    }

    public function tearDown()
    {
        Yii::app()->mongodb->drop();
        parent::tearDown();
    }

    public function testModel()
    {
        $c = User::model();
        $this->assertInstanceOf('EMongoDocument', $c);
    }

    /**
     * @covers EMongoDocument::save
     */
    public function testSaving()
    {
        $c = new User;
        $c->username = 'sammaye';
        $this->assertTrue($c->save());

        $r = User::model()->findAll();
        $this->assertTrue(User::model()->count() > 0);

        foreach ($r as $doc) {
            $doc->username = "dan";
            $this->assertTrue($doc->save());
        }
        // Dan, is it you? 
        $r = User::model()->findOne(['username' => 'dan'], ['username' => 1]);
        $this->assertEquals('dan', $r->username);
    }

    /**
     * @covers EMongoDocument::delete
     */
    public function testDeleting()
    {
        $c = new User;
        $c->username = 'sammaye';
        $this->assertTrue($c->save());
        $r = $c->delete();        
        $this->assertTrue($r->getDeletedCount() == 1);

        $r = User::model()->find();
        $this->assertFalse(User::model()->count() > 0);
    }

    /**
     * @covers EMongoDocument::findOne
     */
    public function testFindOne()
    {
        $c = new User;
        $c->username = 'sally';
        $this->assertTrue($c->save());

        $r = User::model()->findOne(['username' => 'sally'], ['username' => 1]);
        $this->assertTrue($r->count() > 0);
        $this->assertEquals('sally', $r->username);
    }

    /**
     * @covers EMongoDocument::findBy_id
     */
    public function testFindById()
    {
        $c = new User;
        $c->username = 'sammaye';
        $this->assertTrue($c->save());

        $r = User::model()->findBy_id($c->_id);
        $this->assertTrue(!is_null($r));

        $r = User::model()->findBy_id((string) $c->_id);
        $this->assertTrue(!is_null($r));

        $this->assertEquals('sammaye', $r->username);
    }

    /**
     * @covers EMongoDocument::findAllByPk
     */
    public function testFindAllByPk()
    {
        $c = new User;
        $c->username = 'harry';
        $this->assertTrue($c->save());

        $r = User::model()->findAllByPk($c->_id);
        $this->assertTrue(!is_null($r));

        $r = User::model()->findAllByPk((string) $c->_id);
        $this->assertTrue(!is_null($r));

        $r = User::model()->findAllByPk([(string) $c->_id]);
        $this->assertTrue(!is_null($r));

//        $this->assertInstanceOf('EMongoCursor', $r);

        $r = User::model()->findOne(['_id' => $c->_id], ['username' => 1]);
        $this->assertEquals('harry', $r->username);
    }

    /**
     * @covers EMongoDocument::updateByPk
     */
    public function testUpdateByPk()
    {
        $c = new User;
        $c->username = 'sammaye';
        $this->assertTrue($c->save());

        $c->updateByPk($c->_id, ['$set' => ['username' => 'gfgfgf']]);

        $r = User::model()->findOne(['username' => 'gfgfgf']);
        $this->assertInstanceOf('EMongoDocument', $r);
        $this->assertEquals('gfgfgf', $r->username);
    }

    /**
     * @covers EMongoDocument::deleteByPk
     */
    public function testDeleteByPk()
    {
        $c = new User;
        $c->username = 'sammaye';
        $this->assertTrue($c->save());

        $c->deleteByPk($c->_id);

        $r = User::model()->findOne();
        $this->assertNull($r);
    }

    /**
     * @covers EMongoDocument::updateAll
     */
    public function testUpdateAll()
    {
        for ($i = 0; $i < 4; $i++) {
            $c = new User;
            $c->username = 'frodo';
            $this->assertTrue($c->save());
        }

        $c->updateAll(['username' => 'frodo'], ['username' => 'gdgdgd']);

        $r = User::model()->findOne(['username' => 'gdgdgd']);
        $this->assertInstanceOf('EMongoDocument', $r);

        $r = User::model()->find(['username' => 'gdgdgd']);
        $this->assertEquals(4, User::model()->count(['username' => 'gdgdgd']));
    }

    /**
     * @covers EMongoDocument::deleteAll
     */
    public function testDeleteAll()
    {
        $c = new User;
        $c->username = 'sammaye';
        $this->assertTrue($c->save());

        $c->deleteAll();

        $r = User::model()->findOne();
        $this->assertNull($r);

        for ($i = 0; $i < 9; $i++) {
            $c = new User;
            $c->username = 'ringwraith';
            $c->mainSkill = 'LoTR';
            $this->assertTrue($c->save());
        }

        $c = new User;
        $c->username = 'gandalf';
        $c->mainSkill = 'LoTR';
        $this->assertTrue($c->save());

        $r = User::model()->count(['mainSkill' => 'LoTR']);
        $this->assertEquals(10, $r);

        $c->deleteAll(['username' => 'ringwraith']);
        $r = User::model()->count(['mainSkill' => 'LoTR']);
        $this->assertEquals(1, $r);
    }

    /**
     * @covers EMongoDocument::saveAttributes
     */
    public function testSaveAttributes()
    {
        $c = new User;
        $c->username = 'saruman';
        $this->assertTrue($c->save());

        $c->job_title = 'wizard';
        $r = $c->saveAttributes(['job_title']);
        $this->assertNotNull($r->getModifiedCount());

        $r = User::model()->findOne();
        $this->assertTrue(isset($r->job_title));
        $this->assertEquals('wizard', $r->job_title);

        $c = new User;
        $c->username = 'radagast';
        $c->job_title = 'wizard';
        $this->setExpectedException('EMongoException');
        $c->saveAttributes(['job_title']);
    }

    public function testPartialDocuments()
    {
        $u = new User;
        $u->username = 'sammaye';
        $this->assertTrue($u->save());

        $r = User::model()->findOne([], ['username' => 1]);
        $this->assertTrue($r->getIsPartial());

        $p = $r->getProjectedFields();
        $this->assertTrue(isset($p['username'], $p['_id']));
        $this->assertFalse(isset($p['addresses']));

        $r2 = User::model()->find([], ['username' => 1]);
        foreach ($r2 as $row) {
            $this->assertTrue($row->getIsPartial());
        }

        // Assume that the partial field cache since past this point both methods are initialised in the same way

        $this->assertTrue($r->save());
    }

    public function testOneRelation()
    {
        $this->setUpRelationalModel();
        $r = User::model()->findOne();
        $this->assertInstanceOf('EMongoDocument', $r->one_interest);
    }

    public function testManyRelation()
    {
        $this->setUpRelationalModel();
        $r = User::model()->findOne();
        // No longer valid due to relation caching
        //$this->assertInstanceOf('EMongoCursor', $r->many_interests);
        $this->assertTrue(count($r->many_interests) > 0);
    }

    public function testOneDBRefRelation()
    {
        $this->setUpRelationalModel();
        $r = User::model()->findOne();
        $this->assertInstanceOf('Skill', $r->primarySkill);
    }

    public function testManyDBRefRelation()
    {
        $this->setUpRelationalModel();
        $r = User::model()->findOne();
        $secondarySkills = $r->secondarySkills;
        $this->assertTrue(count($secondarySkills) > 0);
        foreach ($secondarySkills as $skill) {
            $this->assertInstanceOf('Skill', $skill);
        }
    }

    public function testEmbeddedRelation()
    {
        $this->setUpRelationalModel();
        $r = User::model()->findOne();
        // No longer valid due to relation caching
        //$this->assertInstanceOf('EMongoCursor', $r->embedInterest);
        $this->assertTrue(count($r->embedInterest) == 0);
    }

    public function testWhereRelation()
    {
        $this->setUpRelationalModel();
        $r = User::model()->findOne();
        $this->assertTrue(count($r->where_interest) > 0);        
    }

    public function testFunctionalRelation()
    {
        $this->setUpRelationalModel();
        $r = User::model()->findOne();

        $rel = $r->many_interests(['name' => 'computers']);
        // No longer valid due to relation caching
        //$this->assertInstanceOf('EMongoCursor', $rel);
        $this->assertTrue(count($rel) > 0);
    }

    /**
     * @covers EMongoTimestampBehaviour
     */
    public function testTimestampBehaviour()
    {
        $c = new User;
        $c->username = 'sammaye';
        $this->assertTrue($c->save());
        $this->assertTrue(isset($c->create_time));

        $c->job_title = 'programmer';
        $this->assertTrue($c->save());
        $this->assertTrue(isset($c->update_time));

        $d = new UserTsTest;
        $d->setScenario('testMe');
        $d->username = 'testman1';
        $this->assertTrue($d->save());
        $this->assertTrue(isset($d->create_time));

        $f = new UserTsTest;
        $f->setScenario('testMeFalse');
        $f->username = 'testman2';
        $this->assertTrue($f->save());
        $this->assertFalse(isset($f->create_time));

        $g = new UserTsTestBroken;
        $g->setScenario('testMeFalse');
        $g->username = 'testman3';
        $this->setExpectedException('CException');

        $h = new UserTsTestBroken2;
        $h->setScenario('testMeFalseOn');
        $h->username = 'testman4';
        $this->setExpectedException('CException');
        $h->save();
    }

    /**
     * @covers EMongoUniqueValidator
     */
    public function testUniqueValidator()
    {
        $c = new User;
        $c->setScenario('testUnqiue');
        $c->username = 'sammaye';
        $this->assertTrue($c->save());

        $c = new User;
        $c->setScenario('testUnqiue');
        $c->username = 'sammaye';
        $this->assertFalse($c->validate());
        $this->assertNotEmpty($c->getError('username'));
    }

    /**
     * @covers ESubdocumentValidator
     */
    public function testArraySubdocumentValidator()
    {
        $c = new User;
        $c->username = 'sammaye';
        $c->addresses = [
            ['road' => 12, 'town' => 'yo', 'county' => 23, 'post_code' => 'g', 'telephone' => 'ggg']
        ];
        $this->assertFalse($c->validate());

        $c = new User;
        $c->username = 'sammaye';
        $c->addresses = [
            ['road' => 's', 'town' => 'yo', 'county' => 'sa', 'post_code' => 'g', 'telephone' => 23]
        ];
        $this->assertTrue($c->validate());
    }

    /**
     * @covers ESubdocumentValidator
     */
    public function testClassSubdocumentValidator()
    {
        $c = new User;
        $c->username = 'sammaye';

        $s = new SocialUrl();
        $s->url = "facebook";
        $s->caption = "social_profile";
        $c->url = $s;

        $this->assertFalse($c->validate());
        $this->assertTrue(!$c->url instanceof SocialUrl);

        $c = new User;
        $c->username = 'sammaye';

        $s = new SocialUrl();
        $s->url = 1;
        $s->caption = 2;
        $c->url = $s;

        $this->assertTrue($c->validate());
        $this->assertTrue(!$c->url instanceof SocialUrl);
    }

    /**
     * @covers ESubdocumentValidator
     * @dataProvider setUpProperSubdocumentErrorsIndexationTest
     */
    public function testProperSubdocumentErrorsIndexation($post)
    {
        $c = new User;
        $c->attributes = $post;
        $this->assertFalse($c->validate());
        $errors = $c->errors;
        $this->assertNotNull($errors['addresses'][1]['telephone']);
    }

    /**
     * @covers EMongoDocument::exists
     */
    public function testExists()
    {
        $c = new User;
        $c->username = 'sammaye';
        $this->assertTrue($c->save());
        $this->assertTrue(User::model()->exists(['username' => 'sammaye']));
    }

    /**
     * @covers EMongoDocument::equals
     */
    public function testEquals()
    {
        $c = new User;
        $c->username = 'sammaye';
        $this->assertTrue($c->save());

        $d = User::model()->findOne(['username' => 'sammaye']);
        $this->assertTrue($c->equals($d));
    }

    public function testScopes()
    {
        $parentDocs = [
            ['username' => 'sam', 'job_title' => 'awesome guy'],
            ['username' => 'john', 'job_title' => 'co-awesome guy'],
            ['username' => 'dan', 'job_title' => 'programmer'],
            ['username' => 'lewis', 'job_title' => 'programmer'],
            ['username' => 'ant', 'job_title' => 'programmer']
        ];

        foreach ($parentDocs as $doc) {
            $c = new User;
            foreach ($doc as $k => $v) {
                $c->$k = $v;
            }
            $this->assertTrue($c->save());
        }

        $u = User::model()->programmers()->count();
        $this->assertTrue($u == 3);
        User::model()->resetScope();
    }

    public function testCleanRefresh()
    {
        $c = new User;
        User::model()->resetScope();
        $c->username = 'sammaye';
        $this->assertTrue($c->save());

        $this->assertTrue($c->clean());
        $this->assertNull($c->username);

        $r = User::model()->findOne();
        $this->assertInstanceOf('EMongoDocument', $r);

        $r->username = 'fgfgfg';
        $r->refresh();
        $this->assertEquals('sammaye', $r->username);
    }

    /**
     * @covers EMongoDocument::getAttributeLabel
     */
    public function testGetAttributeLabel()
    {
        $c = new User;
        $c->username = 'sammaye';
        $this->assertTrue($c->save());

        $this->assertEquals('name', $c->getAttributeLabel('username'));
    }

    public function testSaveCounters()
    {
        $c = new User;
        $c->username = 'sammaye';
        $this->assertTrue($c->save());

        $c->saveCounters(['i' => 1]);
        $this->assertTrue($c->i == 1);

        $d = User::model()->findOne(['username' => 'sammaye']);
        $this->assertTrue($d->i == 1);

        $c->saveCounters(['i' => -1]);
        $this->assertTrue($c->i == 0);

        $e = User::model()->findOne(['username' => 'sammaye']);
        $this->assertTrue($e->i == 0);

        $f = new User;
        $f->username = 'merry';
        $this->setExpectedException('EMongoException');
        $f->saveCounters(['i' => 1]);
    }

    public function testVersioning()
    {
        $m = new versionedDocument();
        $m->name = "sammaye";
        $this->assertTrue($m->save());

        $o = versionedDocument::model()->findOne(['_id' => $m->_id]);
        $o->name = "meh";
        $this->assertTrue($o->save());

        $m->name = "sammaye";
        $this->assertFalse($m->save());
    }

    public function testIncrementVersion()
    {
        $m = new versionedDocument();
        $m->name = "sammaye";
        $this->assertTrue($m->save()); // 1

        $o = versionedDocument::model()->findOne(['_id' => $m->_id]);
        $this->assertTrue($o->incrementVersion()); // 2

        $oo = versionedDocument::model()->findOne(['_id' => $m->_id]);
        $this->assertEquals(2, $oo->version());
    }

    public function testSetVersion()
    {
        $m = new versionedDocument();
        $m->name = "sammaye";
        $this->assertTrue($m->save()); // 1

        $o = versionedDocument::model()->findOne(['_id' => $m->_id]);
        $this->assertTrue($o->setVersion(4)); // 4

        $oo = versionedDocument::model()->findOne(['_id' => $m->_id]);
        $this->assertEquals(4, $oo->version());
    }

    public function testGetLatest()
    {
        $m = new versionedDocument();
        $m->name = "sammaye";
        $this->assertTrue($m->save()); // 1

        $d = clone $m;
        $d->age = 2500;
        $this->assertTrue($d->save()); // 2

        $this->assertEquals(1, $m->version());
        $doc = $m->getLatest();
        $this->assertEquals(2, $doc->version());
    }

}