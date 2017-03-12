<?php
require 'vendor/autoload.php';
date_default_timezone_set('Europe/Kiev');

//$log = new Monolog\Logger('name');
//$log->pushHandler(new Monolog\Handler\StreamHandler('app.txt', Monolog\Logger::WARNING));
//$log->addWarning('Oh Noes.');

$app = new \Slim\Slim( array(
  'view' => new \Slim\Views\Twig()  //Why do we change the default Slim view to the Twig`s view?
));

//Що тут відбувається?
$app->add(new \Slim\Middleware\SessionCookie());

$view = $app->view();
$view->parserOptions = array(
    'debug' => true
);
$view->parserExtensions = array(
    new \Slim\Views\TwigExtension()
);
//Кінець

$app->get('/', function() use($app){
  $app->render('about.twig');
})->name('home');

$app->get('/contact', function() use($app){
  $app->render('contact.twig');
})->name('contact');

$app->post('/contact', function() use($app){
  $name = $app->request->post('name');
  $email = $app->request->post('email');
  $msg = $app->request->post('msg');
  $botChecker = $app->request->post('botChecker');
  
  if(!empty($name) && !empty($email) && !empty($msg) && !isset($botChecker)) {
    $cleanName = trim(filter_var($name, FILTER_SANITIZE_STRING));
    $cleanEmail = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    $cleanMsg = trim(filter_var($msg, FILTER_SANITIZE_STRING));
  } else {
    $app->flash('fail', 'All Necessary Fields Are Required.');
    $app->redirect('/contact');
  }
  
  $transport = Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');
  $mailer = \Swift_Mailer::newInstance($transport);
  
  $message = \Swift_Message::newInstance();
  $message->setSubject('Email From Our Website');
  $message->setFrom(array(
     $cleanEmail => $cleanName
  ));
  $message->setTo(array('treehouse@localhost'));
  $message->setBody($cleanMsg);
  
  $result = $mailer->send($message);

  if($result > 0) {
    $app->flash('success', 'Thanks So Much! You are AWESOME!!!');
    $app->redirect('/');
   
  } else {
    $app->flash('fail', 'So Sorry, Something Went Wrong. Please Try Again!');
    // log that there was an error
    $app->redirect('/contact');
  }
  
  
});

$app->run();