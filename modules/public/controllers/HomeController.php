<?php

class HomeController extends Controller
{
    public function index()
    {
        // Daca utilizatorul este autentificat, il trimitem la dashboard
        if (isset($_SESSION['user_id'])) {
            $controller = new DashboardController();
            return $controller->index();
        }
        
        // Altfel, afisam landing page public
        $controller = new LandingController();
        return $controller->index();
    }
}
