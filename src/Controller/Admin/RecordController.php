<?php
namespace Datascribe\Controller\Admin;

use Datascribe\Form\ItemForm;
use Datascribe\Form\RecordForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class RecordController extends AbstractActionController
{
    public function browseAction()
    {
        $item = $this->datascribe()->getRepresentation(
            $this->params('project-id'),
            $this->params('dataset-id'),
            $this->params('item-id')
        );
        if (!$item) {
            return $this->redirect()->toRoute('admin/datascribe');
        }

        $oItem = $item->item();
        $dataset = $item->dataset();
        $project = $dataset->project();

        $this->setBrowseDefaults('id');
        $query = array_merge(
            $this->params()->fromQuery(),
            ['datascribe_item_id' => $item->id()]
        );
        $response = $this->api()->search('datascribe_records', $query);
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $records = $response->getContent();

        $form = $this->getForm(ItemForm::class, [
            'project' => $project,
            'item' => $item,
        ]);

        if ($this->getRequest()->isPost()) {
            $postData = $this->params()->fromPost();
            $form->setData($postData);
            if ($form->isValid()) {
                $formData = $form->getData();
                $response = $this->api($form)->update('datascribe_items', $item->id(), $formData);
                if ($response) {
                    $this->messenger()->addSuccess('Item successfully updated.'); // @translate
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('project', $project);
        $view->setVariable('dataset', $dataset);
        $view->setVariable('item', $item);
        $view->setVariable('oItem', $oItem);
        $view->setVariable('records', $records);
        return $view;
    }

    public function showDetailsAction()
    {
        $record = $this->datascribe()->getRepresentation(
            $this->params('project-id'),
            $this->params('dataset-id'),
            $this->params('item-id'),
            $this->params('record-id')
        );
        if (!$record) {
            return $this->redirect()->toRoute('admin/datascribe');
        }

        $item = $record->item();
        $dataset = $item->dataset();
        $project = $dataset->project();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('project', $project);
        $view->setVariable('dataset', $dataset);
        $view->setVariable('item', $item);
        $view->setVariable('record', $record);
        return $view;
    }

    public function addAction()
    {
        $item = $this->datascribe()->getRepresentation(
            $this->params('project-id'),
            $this->params('dataset-id'),
            $this->params('item-id')
        );
        if (!$item) {
            return $this->redirect()->toRoute('admin/datascribe');
        }

        $oItem = $item->item();
        $dataset = $item->dataset();
        $project = $dataset->project();

        $form = $this->getForm(RecordForm::class, [
            'dataset' => $dataset,
        ]);

        if ($this->getRequest()->isPost()) {
            $postData = $this->params()->fromPost();
            $form->setData($postData);
            if ($form->isValid()) {
                $formData = $form->getData();
                $formData['o-module-datascribe:item']['o:id'] = $item->id();
                $response = $this->api($form)->create('datascribe_records', $formData);
                if ($response) {
                    $this->messenger()->addSuccess('Record successfully created.'); // @translate
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('project', $project);
        $view->setVariable('dataset', $dataset);
        $view->setVariable('item', $item);
        $view->setVariable('oItem', $oItem);
        return $view;
    }

    public function editAction()
    {
        $record = $this->datascribe()->getRepresentation(
            $this->params('project-id'),
            $this->params('dataset-id'),
            $this->params('item-id'),
            $this->params('record-id')
        );
        if (!$record) {
            return $this->redirect()->toRoute('admin/datascribe');
        }

        $item = $record->item();
        $oItem = $item->item();
        $dataset = $item->dataset();
        $project = $dataset->project();

        $form = $this->getForm(RecordForm::class, [
            'dataset' => $dataset,
            'record' => $record,
        ]);

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('project', $project);
        $view->setVariable('dataset', $dataset);
        $view->setVariable('item', $item);
        $view->setVariable('oItem', $oItem);
        $view->setVariable('record', $record);
        return $view;
    }

    public function showAction()
    {
    }
}
