<?php
namespace tool;
use Utils;
class FormBuilderTest extends \BaseTest{
  
  public function testOptions(){
    
      $rows = [['id'=>1, 'name'=>'foo', 'prop'=>'x']
      , ['id'=>2, 'name'=>'bar', 'prop'=>'y']
      ];
      
      $expected = [1=>'foo', 2=>'bar'];
      $this->assertEquals($expected, FormBuilder::buildOptions($rows) );
      
      $expected = '<option value="1">foo</option><option value="2">bar</option>';
      $this->assertEquals($expected, FormBuilder::options_for_select( FormBuilder::buildOptions($rows)) );
      
      //return;
      
      $objects = [1 => ['id'=>1, 'name'=>'foo', 'prop'=>'x']
       , 2 => ['id'=>2, 'name'=>'bar', 'prop'=>'y']
      ];
      
      $expected = '<option value="1" data-prop="x">foo</option>';
      $actual = FormBuilder::options_from_collection( [$rows[0]] );
      Utils::log($expected);
      Utils::log($actual);
      $this->assertEquals($expected, $actual );
      
      $expected = '<option value="1" data-prop="x">foo</option><option value="2" data-prop="y">bar</option>';
      $actual = FormBuilder::options_from_collection( $rows );
      Utils::log($expected);
      Utils::log($actual);
      $this->assertEquals($expected, $actual );
      
      $expected = '<option value="1" data-prop="x">foo</option><option value="2" data-prop="y" selected>bar</option>';
      $actual = FormBuilder::options_from_collection( $rows, 2 );
      Utils::log($expected);
      Utils::log($actual);
      $this->assertEquals($expected, $actual );
  	   
  }
  
  function testCreateTag(){
      $this->assertEquals('<tag>', FormBuilder::createTag('tag'));
      $this->assertEquals('<tag>', FormBuilder::createTag('tag', null));
      $this->assertEquals('<tag>', FormBuilder::createTag('tag', false));
      
      $this->assertEquals('<tag></tag>', FormBuilder::createTag('tag', [], ''));
      $this->assertEquals('<tag></tag>', FormBuilder::createTag('tag', null, ''));
      $this->assertEquals('<tag></tag>', FormBuilder::createTag('tag', false, ''));
      
      $this->assertEquals('<tag>foo</tag>', FormBuilder::createTag('tag', [], 'foo'));
      $this->assertEquals('<tag x="0">foo</tag>', FormBuilder::createTag('tag', ['x'=>0], 'foo'));
      $this->assertEquals('<tag x="0" y="beta">foo</tag>', FormBuilder::createTag('tag', ['x'=>0, 'y'=>'beta'], 'foo'));
  }
  
  function testCreateOption(){
        $this->assertEquals('<option value="foo">bar</option>', FormBuilder::createTag('option', ['value'=>'foo'], 'bar'));
        $this->assertEquals('<option value="foo" data-baz="paz">bar</option>', 
                FormBuilder::createTag('option', ['value'=>'foo', 'data-baz'=>'paz'], 'bar'));
              
  }
   
}