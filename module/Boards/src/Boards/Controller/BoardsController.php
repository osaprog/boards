<?php

namespace Boards\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Boards\Form\BoardsForm;
use Zend\Debug\Debug;
use Zend\Session\Container;

class BoardsController extends AbstractActionController {

    protected $boardsTable;

    const NO_PARENT_ID = -1;
    const COUNT_ITEMS_PER_PAGE = 5;

    protected $_mainTreeOutput;
    protected $_threadsOutput;
    protected $_postsOutput;

    public function indexAction() {
        return new ViewModel(array(
            'output' => $this->_getMainTreeOutput())
        );
    }

    public function threadsAction() {
        return new ViewModel(array(
            'output' => $this->_getThreadsOutput())
        );
    }

    public function postsAction() {
        return new ViewModel(array(
            'output' => $this->_getPostsOutput())
        );
    }

    /*
     * Get Tree Output from cache, if available, otherwise apply  _parseTree function
     */

    protected function _getMainTreeOutput() {
        if ($this->_mainTreeOutput) {
            return $this->_mainTreeOutput;
        } else {
            return $this->_parseTree(self::NO_PARENT_ID, $this->getBoardsTable()->fetch());
        }
    }

    /*
     * Get Threads Output from cache, if available, otherwise apply _getThreads function
     */

    protected function _getThreadsOutput() {
        if ($this->_threadsOutput) {
            return $this->_threadsOutput;
        } else {
            return $this->_getThreads();
        }
    }

    /*
     * Get Posts data from by appling GET API Request, and prepare paging 
     */

    protected function _getPostsOutput() {
        $threadid = (int) $this->params()->fromRoute('id', 0);
        $start = 0;
        if (isset($_GET['page'])) {
            $start = $_GET['page'];
        }

        $session = new Container('totals');
        if (!isset($session->total[$threadid])) {
            $response = $this->getBoardsTable()->fetch('/post/threadid/' . $threadid);
            $total = count($response);
            $session->total[$threadid] = $total;
        } else {
            $total = $session->total[$threadid];
        }
        $response = $this->getBoardsTable()->fetch('/post/threadid/' . $threadid . '/' . $start . '/5');
        return $this->_postPaging($response, $total, $threadid);
    }

    /*
     *  Parse the parent child tree recursively, and prepare the html menu
     */

    protected function _parseTree($root, $tree) {

        if (!is_null($tree) && count($tree) > 0) {
            $this->_mainTreeOutput .= '<ul>';
            foreach ($tree as $forum) {

                if ($forum['parentid'] == $root) {

                    if ($forum['cancontainthreads'] == 0) {
                        $this->_mainTreeOutput .= '<li><a href="#">' . $forum['title'] . "</a>";

                        if (isset($forum['children'])) {
                            $this->_parseTree($forum['forumid'], $forum['children']);
                        }
                        $this->_mainTreeOutput .= '</li>';
                    } else {
                        $this->_mainTreeOutput .= '<li><a style="color: gray;" href="boards/threads/' . $forum['forumid'] . '">' . $forum['title'] . "</a></li>";
                    }
                }
            }
            $this->_mainTreeOutput .= '</ul>';
        }
        return $this->_mainTreeOutput;
    }

    /*
     * Get Data Source - To apply GET API Requests
     */

    public function getBoardsTable() {
        if (!$this->boardsTable) {
            $sm = $this->getServiceLocator();
            $this->boardsTable = $sm->get('Boards\Model\BoardsTable');
        }
        return $this->boardsTable;
    }

    protected function sortThreads($a, $b) {
        return $b['views'] - $a['views'];
    }

    protected function _getTopViewThreads($all, $count = 5) {
        // Sort the Threads by number of Views using (user-defined comparison function) 
        usort($all, array($this, 'sortThreads'));
        // Return top 5 based on the number of views after sorting
        return array_slice($all, 0, $count);
    }

    /*
     * Fetch threads from API Request and get top threads
     */

    protected function _getThreads() {
        $forumid = (int) $this->params()->fromRoute('id', 0);
        $topThreads = $this->_getTopViewThreads($this->getBoardsTable()->fetch('/thread/forumid/' . $forumid));
        foreach ($topThreads as $thread) {
            $this->_threadsOutput .='<a href="/boards/posts/' . $thread['threadid'] . '">' . $thread['title'] . '</a></br>';
        }
        return $this->_threadsOutput;
    }

    protected function _postPaging($threads, $total, $threadId) {

        try {

            // How many items to list per page
            $limit = self::COUNT_ITEMS_PER_PAGE;

            // How many pages will there be
            $pages = ceil($total / $limit);

            // What page are we currently on?
            $page = min($pages, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, array(
                'options' => array(
                    'default' => 1,
                    'min_range' => 1,
                ),
            )));

            // Calculate the offset for the query
            $offset = ($page - 1) * $limit;

            // Some information to display to the user
            $start = $offset + 1;
            $end = min(($offset + $limit), $total);

            // The "back" link
            $prevlink = ($page > 1) ? '<a href="?page=1" title="First page">&laquo;</a> <a href="?page=' . ($page - 1) . '" title="Previous page">&lsaquo;</a>' : '<span class="disabled">&laquo;</span> <span class="disabled">&lsaquo;</span>';

            // The "forward" link
            $nextlink = ($page < $pages) ? '<a href="?page=' . ($page + 1) . '" title="Next page">&rsaquo;</a> <a href="?page=' . $pages . '"  title="Last page">&raquo;</a>' : '<span class="disabled">&rsaquo;</span> <span class="disabled">&raquo;</span>';

            // Display the paging information
            $this->_postsOutput .= '<div id="paging"><p>' . $prevlink . ' Page ' . $page . ' of ' . $pages . ' pages, displaying ' . $start . '-' . $end . ' of ' . $total . ' results ' . $nextlink . ' </p></div>';

            foreach ($threads as $post) {
                $joinDate = "";
                if (isset($post['userjoindate']) & is_int($post['userjoindate'])) {
                    $joinDate = " - joined on " . date('m/d/Y', $post['userjoindate']);
                }
                $this->_postsOutput .= "<i>Username:</i>" . $post['username'] . $joinDate . "</br>";
                $this->_postsOutput .= "<i>Title:" . $post['title'] . "</i></br></br>";
                $this->_postsOutput .= "<p>" . $post['pagetext'] . "</p>";
                $this->_postsOutput .= "<br><hr>";
            }
        } catch (Exception $e) {
            $this->_postsOutput .= '<p>' . $e->getMessage() . '</p>';
        }

        return $this->_postsOutput;
    }

}
