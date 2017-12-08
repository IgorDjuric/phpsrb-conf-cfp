<?php

namespace OpenCFP\Http\Controller\Reviewer;

use OpenCFP\Domain\Model\User;
use OpenCFP\Domain\Services\Pagination;
use OpenCFP\Domain\Speaker\SpeakerProfile;
use OpenCFP\Http\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class SpeakersController extends BaseController
{
    public function indexAction(Request $req)
    {
        $search = $req->get('search');
        
        if (!empty($req->get('order_by'))) {
            $orderBy = $req->get('order_by');
            $order = $req->get('order');

            $speakers = User::search($search, $orderBy, $order)->get()->toArray();
        } else {
            $speakers = User::search($search)->get()->toArray();
        }

        // Set up our page stuff
        $pagerfanta = new Pagination($speakers);
        $pagerfanta->setCurrentPage($req->get('page'));
        
        // Paginator url
        if (!empty($req->get('order_by'))) {
            $pagination = $pagerfanta->createView('/reviewer/speakers?search='. $search .'&order_by='. $orderBy. '&order='. $order .'&');
        } elseif (!empty($req->get('search'))){
            $pagination = $pagerfanta->createView('/reviewer/speakers?search='. $search .'&');
        } else {
            $pagination = $pagerfanta->createView('/reviewer/speakers?');
        }

        $templateData = [
            'pagination' => $pagination,
            'speakers' => $pagerfanta->getFanta(),
            'page' => $pagerfanta->getCurrentPage(),
            'search' => $search ?: '',
        ];

        return $this->render('reviewer/speaker/index.twig', $templateData);
    }

    public function viewAction(Request $req)
    {
        $speakerDetails = User::where('id', $req->get('id'))->first();

        if (!$speakerDetails instanceof User) {
            $this->service('session')->set('flash', [
                'type' => 'error',
                'short' => 'Error',
                'ext' => 'Could not find requested speaker',
            ]);

            return $this->app->redirect($this->url('reviewer_speakers'));
        }

        $talks = $speakerDetails->talks()->get()->toArray();
        $templateData = [
            'speaker' => new SpeakerProfile($speakerDetails),
            'talks' => $talks,
            'photo_path' => '/uploads/',
            'page' => $req->get('page'),
        ];

        return $this->render('reviewer/speaker/view.twig', $templateData);
    }
}
