<?php
namespace App\Controllers;

use App\Core\BaseController;

class AdminController extends BaseController {

    public function dashboard(): void {
        // Données factices pour tester l'affichage
        $stats = [
            'total_posts' => 15,
            'total_comments' => 42,
            'pending_comments' => 3,
            'total_users' => 8
        ];

        $recentPosts = [
            (object)[
                'titre' => 'Top 5 des Traces VTT Enduro',
                'date_creation' => date('Y-m-d H:i:s'),
                'statut' => 'Public'
            ],
            (object)[
                'titre' => 'Réglage de la suspension',
                'date_creation' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'statut' => 'Public'
            ]
        ];

        $recentComments = [
            (object)[
                'nom_auteur' => 'Nicolas Rider',
                'contenu' => 'Super article, je connaissais pas la piste de l\'Écureuil !',
                'date_commentaire' => date('Y-m-d H:i:s'),
                'statut' => 'Approuvé',
                'article_titre' => 'Top 5 des Traces VTT Enduro'
            ]
        ];

        $this->render('admin/dashboard.twig', [
            'page_title' => 'Tableau de Bord Admin',
            'stats' => $stats,
            'recent_posts' => $recentPosts,
            'recent_comments' => $recentComments
        ]);
    }
}