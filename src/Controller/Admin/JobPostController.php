<?php

namespace App\Controller\Admin;

use App\Entity\JobPost;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;

class JobPostController extends AbstractController
{
    #[AdminRoute('/offres-d-emploi/{id}/apercu', name: 'job_post_preview')]
    public function preview(JobPost $jobPost): Response
    {
        return $this->render('admin/job_post/preview.html.twig', [
            'jobPost' => $jobPost,
        ]);
    }
}
