<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use App\Entity\Employee;
use App\Entity\Appointment;




class PagesController extends AbstractController
{




    private function getUserById($id){
        $repository = $this->getDoctrine()->getRepository(Employee::class);
        $user = $repository->find($id);
        return $user;
    }

    private function checkIfLoggedIn(SessionInterface $session){
        if( $session->get('user_id', -1) == -1 ) return false; else return true;
    }





    public function login(SessionInterface $session){
        if( $this->checkIfLoggedIn($session) ) return $this->redirectToRoute('schedule');

        return $this->render('pages/login.html.twig', [
            'controller_name' => 'PagesController',
        ]);
    }

    public function login_action(SessionInterface $session){
        $request = Request::createFromGlobals();
        $login_username = $request->query->get('login_username');
        $login_password = $request->query->get('login_password');
        //die($login_username." ".$login_password);
    
        $repository = $this->getDoctrine()->getRepository(Employee::class);

        $user = $repository->findOneBy(
            ['email' => $login_username]
        );

        if( !$user ) return $this->redirectToRoute('login', array('error' => 'wrong_username_or_password'));

        if( $user->getPassword() == $login_password  ){
            $session->set('user_id', ($user->getId()) );

            if( $user->getPost() == "Admin" ){
                $session->set('user_permission', 1);
            }else{
                $session->set('user_permission', 0);
            }

            return $this->redirectToRoute('schedule');
        } else {
            return $this->redirectToRoute('login', array('error' => 'wrong_username_or_password'));
        }
    }

    public function logout_action(SessionInterface $session){
        $session->set('user_permission', -1);
        $session->set('user_id', -1);
        return $this->redirectToRoute('login');
    }





    public function employees(SessionInterface $session){
        if( !($this->checkIfLoggedIn($session)) ) return $this->redirectToRoute('login');
        if( $session->get('user_permission', -1) != 1 ) return $this->redirectToRoute('schedule');

        $repository = $this->getDoctrine()->getRepository(Employee::class);
        $users = $repository->findAll();

        return $this->render('pages/employees.html.twig', [
            'controller_name' => 'PagesController',
            'users' => $users,
        ]);
    }

    public function add_employee_action(SessionInterface $session){
        if( !($this->checkIfLoggedIn($session)) ) return $this->redirectToRoute('login');
        if( $session->get('user_permission', -1) != 1 ) return $this->redirectToRoute('schedule');
        
        $request = Request::createFromGlobals();
        $entityManager = $this->getDoctrine()->getManager();

        $newUser = new Employee();
        $newUser->setFirstName( $request->query->get('first_name') );
        $newUser->setLastName( $request->query->get('last_name') );
        $newUser->setPost( $request->query->get('post') );
        $newUser->setPassword( $request->query->get('password') );
        $newUser->setEmail( $request->query->get('email') );

        $defaultSchedule = "";
        for($i = 0; $i<24*7; $i++) $defaultSchedule = $defaultSchedule."0";
        $newUser->setSchedulePattern( $defaultSchedule );

        $entityManager->persist($newUser);
        $entityManager->flush();

        return $this->redirectToRoute('employees');
    }

    public function delete_employee_action(SessionInterface $session, $id){
        if( !($this->checkIfLoggedIn($session)) ) return $this->redirectToRoute('login');
        if( $session->get('user_permission', -1) != 1 ) return $this->redirectToRoute('schedule');

        $entityManager = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository(Employee::class);
        $toBeDeleted = $repository->find($id);

        if( !$toBeDeleted ) return $this->redirectToRoute('employees');

        $entityManager->remove($toBeDeleted);
        $entityManager->flush();
        
        return $this->redirectToRoute('employees');
    }

    public function edit_employee_action(SessionInterface $session, $id){
        if( !($this->checkIfLoggedIn($session)) ) return $this->redirectToRoute('login');
        if( $session->get('user_permission', -1) != 1 ) return $this->redirectToRoute('schedule');

        $request = Request::createFromGlobals();
        
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository(Employee::class);
        $toBeEdited = $repository->find($id);

        if( !$toBeEdited ) return $this->redirectToRoute('employees');

        $toBeEdited->setFirstName( $request->query->get('first_name') );
        $toBeEdited->setLastName( $request->query->get('last_name') );
        $toBeEdited->setPost( $request->query->get('post') );
        $toBeEdited->setPassword( $request->query->get('password') );
        $toBeEdited->setEmail( $request->query->get('email') );

        $entityManager->flush();
        
        return $this->redirectToRoute('employees');
    }

    public function edit_employee_pattern_action(SessionInterface $session, $id){
        if( !($this->checkIfLoggedIn($session)) ) return $this->redirectToRoute('login');
        if( $session->get('user_permission', -1) != 1 ) return $this->redirectToRoute('schedule');

        $request = Request::createFromGlobals();
        
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository(Employee::class);
        $toBeEdited = $repository->find($id);

        if( !$toBeEdited ) return $this->redirectToRoute('employees');

        $toBeEdited->setSchedulePattern( $request->query->get('pattern') );

        $entityManager->flush();
        
        return $this->redirectToRoute('employees');
    }



    

    public function schedule(SessionInterface $session){
        if( !($this->checkIfLoggedIn($session)) ) return $this->redirectToRoute('login');

        $repository = $this->getDoctrine()->getRepository(Appointment::class);
        $request = Request::createFromGlobals();
        //$date = $request->query->get('date', date("Y-m-d"));
        $date = date("Y-m-d");
        $repositoryEmployee = $this->getDoctrine()->getRepository(Employee::class);
        $users = $repositoryEmployee->findAll();

        
        $allAppointments = $repository->findAll();

        $appointments = array();

        foreach($allAppointments as $appointment){
            $d = getdate( strtotime($date) );
            $d2 = getdate( ($appointment->getDate())->getTimestamp() );
            if( $d["year"]==$d2["year"] && $d["mon"]==$d2["mon"] && $d["mday"]==$d2["mday"] ){
                array_push( $appointments, $appointment );
            }
        }

        return $this->render('pages/schedule.html.twig', [
            'controller_name' => 'PagesController',
            'appointments' => $appointments,
            'user' => $users,
            'today_date' => date("Y-m-d"),
            'wday' => getdate( strtotime($date) )['wday']
        ]);
    }

    public function my_schedule(SessionInterface $session){
        if( !($this->checkIfLoggedIn($session)) ) return $this->redirectToRoute('login');
        if( $session->get('user_permission', -1) == 1 ) return $this->redirectToRoute('schedule');

        $request = Request::createFromGlobals();
        $user_id = $session->get('user_id');
        $date = $request->query->get('date', date("Y-m-d"));

        $repository = $this->getDoctrine()->getRepository(Appointment::class);
        $sameDoctorAppointments = $repository->findBy(
            ['doctor' => $user_id]
        );

        $appointments = array();

        foreach($sameDoctorAppointments as $appointment){
            $d = getdate( strtotime($date) );
            $d2 = getdate( ($appointment->getDate())->getTimestamp() );
            if( $d["year"]==$d2["year"] && $d["mon"]==$d2["mon"] && $d["mday"]==$d2["mday"] ){
                array_push( $appointments, $appointment );
            }
        }

        $user = $this->getUserById($user_id);

        return $this->render('pages/my_schedule.html.twig', [
            'controller_name' => 'PagesController',
            'appointments' => $appointments,
            'user' => $user,
            'today_date' => date("Y-m-d"),
            'wday' => getdate( strtotime($date) )['wday']
        ]);
    }

    public function get_schedule_day(SessionInterface $session){
        if( !($this->checkIfLoggedIn($session)) ) return $this->redirectToRoute('login');
        if( $session->get('user_permission', -1) == 1 ) return $this->redirectToRoute('schedule');

        $request = Request::createFromGlobals();
        $date = $request->query->get('date');

        return $this->redirectToRoute('my_schedule', array('date' => $date));
    }



    public function add_appointment_action(SessionInterface $session){
        if( !($this->checkIfLoggedIn($session)) ) return $this->redirectToRoute('login');
        if( $session->get('user_permission', -1) == 1 ) return $this->redirectToRoute('schedule');
        
        $request = Request::createFromGlobals();
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository(Appointment::class);

        $user_id = $session->get('user_id');

        $user = $this->getUserById($user_id);

        $date = $request->query->get('date',date("Y-m-d"));
        if( $date=="" || $date===NULL ){
            $date = date("Y-m-d");
        }

        $sameDoctorAppointments = $repository->findBy(
            ['doctor' => $user_id]
        );

        $sameDayAppointments = array();

        foreach($sameDoctorAppointments as $appointment){
            $d = getdate( strtotime($date) );
            $d2 = getdate( ($appointment->getDate())->getTimestamp() );
            if( $d["year"]==$d2["year"] && $d["mon"]==$d2["mon"] && $d["mday"]==$d2["mday"] ){
                array_push( $sameDayAppointments, $appointment );
            }
        }

        $s = $request->query->get('time');
        $b = $s + $request->query->get('duration') - 1;

        $conflict = 0;

        if($b>23) $conflict = 2;

        foreach($sameDayAppointments as $appointment){
            $ss = $appointment->getTime();
            $bb = $ss + $appointment->getDuration() - 1;

            if( $bb<$s || $ss>$b ){
                } else {
                    $conflict = 1;
                }
        }

        if( $conflict==1 ) return $this->redirectToRoute('my_schedule', array('error' => 'time_reserved'));
        if( $conflict==2 ) return $this->redirectToRoute('my_schedule', array('error' => 'past_midnight'));

        

        $newAppointment = new Appointment();
        $newAppointment->setName( $request->query->get('name') );
        $newAppointment->setDoctor( $user_id );
        $newAppointment->setDate( \DateTime::createFromFormat('Y-m-d', $date) );
        $newAppointment->setTime( $s );
        $newAppointment->setDuration( $request->query->get('duration') );
        $newAppointment->setType( $request->query->get('type') );
        
        $entityManager->persist($newAppointment);
        $entityManager->flush();

        //die(":: ".$date);
        
        $pattern = $user->getSchedulePattern();
        $delta = getdate( strtotime($date) )['wday'];
        $delta--; if($delta==-1) $delta = 6; 
        $freeTimeWork = false;
        for($i = $s; $i<=$b; $i++){
            if( $pattern[$i]==0 ){
                $freeTimeWork = true;
            }
        }
        if( $freeTimeWork )
            return $this->redirectToRoute('my_schedule', array('date' => $date, 'warning' => 'work_in_free_time'));
        else
            return $this->redirectToRoute('my_schedule', array('date' => $date));
    }

    public function edit_appointment_action(SessionInterface $session, $id){
        if( !($this->checkIfLoggedIn($session)) ) return $this->redirectToRoute('login');
        if( $session->get('user_permission', -1) == 1 ) return $this->redirectToRoute('schedule');
        
        $request = Request::createFromGlobals();
        
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository(Appointment::class);
        $toBeEdited = $repository->find($id);

        if( !$toBeEdited ) return $this->redirectToRoute('my_schedule');

        $user_id = $session->get('user_id');

        $user = $this->getUserById($user_id);

        $date = $request->query->get('date',date("Y-m-d"));
        if( $date=="" || $date===NULL ){
            $date = date("Y-m-d");
        }

        $sameDoctorAppointments = $repository->findBy(
            ['doctor' => $user_id]
        );

        $sameDayAppointments = array();

        foreach($sameDoctorAppointments as $appointment){
            if( $appointment->getId() == $id ) continue;

            $d = getdate( strtotime($date) );
            $d2 = getdate( ($appointment->getDate())->getTimestamp() );
            if( $d["year"]==$d2["year"] && $d["mon"]==$d2["mon"] && $d["mday"]==$d2["mday"] ){
                array_push( $sameDayAppointments, $appointment );
            }
        }

        $s = $request->query->get('time');
        $b = $s + $request->query->get('duration') - 1;

        $conflict = 0;

        if($b>23) $conflict = 2;

        foreach($sameDayAppointments as $appointment){
            if( $appointment->getId()==$id ) continue;

            $ss = $appointment->getTime();
            $bb = $ss + $appointment->getDuration() - 1;

            if( $bb<$s || $ss>$b ){
                } else {
                    $conflict = 1;
                }
        }

        if( $conflict==1 ) return $this->redirectToRoute('my_schedule', array('error' => 'time_reserved'));
        if( $conflict==2 ) return $this->redirectToRoute('my_schedule', array('error' => 'past_midnight'));

        $toBeEdited->setName( $request->query->get('name') );
        $toBeEdited->setDoctor( $user_id );
        $toBeEdited->setDate( \DateTime::createFromFormat('Y-m-d', $date) );
        $toBeEdited->setTime( $request->query->get('time') );
        $toBeEdited->setDuration( $request->query->get('duration') );
        $toBeEdited->setType( $request->query->get('type') );
        
        $entityManager->persist($toBeEdited);
        $entityManager->flush();

        return $this->redirectToRoute('my_schedule', array('date' => $date));
    }

    public function delete_appointment_action(SessionInterface $session, $id){
        if( !($this->checkIfLoggedIn($session)) ) return $this->redirectToRoute('login');
        if( $session->get('user_permission', -1) == 1 ) return $this->redirectToRoute('schedule');

        $entityManager = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository(Appointment::class);
        $toBeDeleted = $repository->find($id);

        if( !$toBeDeleted ) return $this->redirectToRoute('my_schedule');

        $entityManager->remove($toBeDeleted);
        $entityManager->flush();
        
        return $this->redirectToRoute('my_schedule');
    }


}
