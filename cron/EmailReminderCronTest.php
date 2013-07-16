<?php
namespace cron;
use model\ReminderType;
class EmailReminderCronTest extends ReminderCronTest{
  
  protected $type = ReminderType::EMAIL;
  
  protected function createInstance(){
    return new \cron\EmailReminderCron;
    }
   
}