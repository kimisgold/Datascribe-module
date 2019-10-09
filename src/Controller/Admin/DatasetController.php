<?php
namespace Datascribe\Controller\Admin;

use Datascribe\Form\DatasetForm;
use Datascribe\Form\DatasetSyncForm;
use Datascribe\Job\SyncDataset;
use Omeka\Form\ConfirmForm;
use Omeka\Stdlib\Message;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class DatasetController extends AbstractActionController
{
    public function addAction()
    {
        try {
            $project = $this->api()->read('datascribe_projects', $this->params('project-id'))->getContent();
        } catch (NotFoundException $e) {
            return $this->redirect()->toRoute('admin/datascribe');
        }
        $form = $this->getForm(DatasetForm::class);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $formData['o-module-datascribe:project']['o:id'] = $project->id();
                $formData['o:item_set'] = ['o:id' => $formData['o:item_set']];
                $formData['o:is_public'] = $this->params()->fromPost('o:is_public');
                $response = $this->api($form)->create('datascribe_datasets', $formData);
                if ($response) {
                    $this->messenger()->addSuccess('DataScribe dataset successfully created.'); // @translate
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('project', $project);
        return $view;
    }

    public function editAction()
    {
        try {
            $dataset = $this->api()->read('datascribe_datasets', $this->params('dataset-id'))->getContent();
        } catch (NotFoundException $e) {
            return $this->redirect()->toRoute('admin/datascribe');
        }
        $form = $this->getForm(DatasetForm::class);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $formData['o:item_set'] = ['o:id' => $formData['o:item_set']];
                $formData['o:is_public'] = $this->params()->fromPost('o:is_public');
                $response = $this->api($form)->update('datascribe_datasets', $this->params('dataset-id'), $formData);
                if ($response) {
                    $this->messenger()->addSuccess('DataScribe dataset successfully edited.'); // @translate
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        } else {
            $data = $dataset->jsonSerialize();
            $data['o:item_set'] = $data['o:item_set'] ? $data['o:item_set']->id() : null;
            $form->setData($data);
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('project', $dataset->project());
        $view->setVariable('dataset', $dataset);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('datascribe_datasets', $this->params('dataset-id'));
                if ($response) {
                    $this->messenger()->addSuccess('DataScribe dataset successfully deleted'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    public function browseAction()
    {
        try {
            $project = $this->api()->read('datascribe_projects', $this->params('project-id'))->getContent();
        } catch (NotFoundException $e) {
            return $this->redirect()->toRoute('admin/datascribe');
        }

        $this->setBrowseDefaults('created');
        $response = $this->api()->search('datascribe_datasets', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $datasets = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('project', $project);
        $view->setVariable('datasets', $datasets);
        return $view;
    }

    public function showDetailsAction()
    {
        try {
            $dataset = $this->api()->read('datascribe_datasets', $this->params('dataset-id'))->getContent();
        } catch (NotFoundException $e) {
            return $this->redirect()->toRoute('admin/datascribe');
        }

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('project', $dataset->project());
        $view->setVariable('dataset', $dataset);
        return $view;
    }

    public function showAction()
    {
        return $this->redirect()->toRoute('admin/datascribe-item', ['action' => 'browse'], true);
    }

    public function syncAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(DatasetSyncForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $job = $this->jobDispatcher()->dispatch(
                    SyncDataset::class,
                    ['datascribe_dataset_id' => $this->params('dataset-id')]
                );
                $message = new Message(
                    'Syncing DataScribe dataset. This may take a while. %s', // @translate
                    sprintf(
                        '<a href="%s">%s</a>',
                        htmlspecialchars($this->url()->fromRoute('admin/id', ['controller' => 'job', 'id' => $job->getId()])),
                        $this->translate('See this job for sync progress.')
                    ));
                $message->setEscapeHtml(false);
                $this->messenger()->addSuccess($message);
                return $this->redirect()->toRoute('admin/datascribe-item', ['action' => 'browse'], true);
            }
        }
        return $this->redirect()->toRoute('admin/datascribe-dataset', ['action' => 'browse'], true);
    }
}
