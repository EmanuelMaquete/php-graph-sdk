<?php
/**
 * Copyright 2017 Facebook, Inc.
 *
 * You are hereby granted a non-exclusive, worldwide, royalty-free license to
 * use, copy, modify, and distribute this software in source code or binary
 * form for use in connection with the web services and APIs provided by
 * Facebook.
 *
 * As with any software that integrates with the Facebook platform, your use
 * of this software is subject to the Facebook Developer Principles and
 * Policies [http://developers.facebook.com/policy/]. This copyright notice
 * shall be included in all copies or substantial portions of the software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */
namespace Facebook\GraphNode;

use Facebook\Request;
use Facebook\Url\UrlManipulator;
use Facebook\Exception\SDKException;

/**
 * @package Facebook
 */
class GraphEdge extends Collection
{
    /**
     * @var Request the original request that generated this data
     */
    protected $request;

    /**
     * @var array an array of Graph meta data like pagination, etc
     */
    protected $metaData = [];

    /**
     * @var null|string the parent Graph edge endpoint that generated the list
     */
    protected $parentEdgeEndpoint;

    /**
     * @var null|string the subclass of the child GraphNode's
     */
    protected $subclassName;

    /**
     * Init this collection of GraphNode's.
     *
     * @param Request     $request            the original request that generated this data
     * @param array       $data               an array of GraphNode's
     * @param array       $metaData           an array of Graph meta data like pagination, etc
     * @param null|string $parentEdgeEndpoint the parent Graph edge endpoint that generated the list
     * @param null|string $subclassName       the subclass of the child GraphNode's
     */
    public function __construct(Request $request, array $data = [], array $metaData = [], $parentEdgeEndpoint = null, $subclassName = null)
    {
        $this->request = $request;
        $this->metaData = $metaData;
        $this->parentEdgeEndpoint = $parentEdgeEndpoint;
        $this->subclassName = $subclassName;

        parent::__construct($data);
    }

    /**
     * Gets the parent Graph edge endpoint that generated the list.
     *
     * @return null|string
     */
    public function getParentGraphEdge()
    {
        return $this->parentEdgeEndpoint;
    }

    /**
     * Gets the subclass name that the child GraphNode's are cast as.
     *
     * @return null|string
     */
    public function getSubClassName()
    {
        return $this->subclassName;
    }

    /**
     * Returns the raw meta data associated with this GraphEdge.
     *
     * @return array
     */
    public function getMetaData()
    {
        return $this->metaData;
    }

    /**
     * Returns the next cursor if it exists.
     *
     * @return null|string
     */
    public function getNextCursor()
    {
        return $this->getCursor('after');
    }

    /**
     * Returns the previous cursor if it exists.
     *
     * @return null|string
     */
    public function getPreviousCursor()
    {
        return $this->getCursor('before');
    }

    /**
     * Returns the cursor for a specific direction if it exists.
     *
     * @param string $direction The direction of the page: after|before
     *
     * @return null|string
     */
    public function getCursor($direction)
    {
        if (isset($this->metaData['paging']['cursors'][$direction])) {
            return $this->metaData['paging']['cursors'][$direction];
        }

        return null;
    }

    /**
     * Generates a pagination URL based on a cursor.
     *
     * @param string $direction The direction of the page: next|previous
     *
     * @throws SDKException
     *
     * @return null|string
     */
    public function getPaginationUrl($direction)
    {
        $this->validateForPagination();

        // Do we have a paging URL?
        if (!isset($this->metaData['paging'][$direction])) {
            return null;
        }

        $pageUrl = $this->metaData['paging'][$direction];

        return UrlManipulator::baseGraphUrlEndpoint($pageUrl);
    }

    /**
     * Validates whether or not we can paginate on this request.
     *
     * @throws SDKException
     */
    public function validateForPagination()
    {
        if ($this->request->getMethod() !== 'GET') {
            throw new SDKException('You can only paginate on a GET request.', 720);
        }
    }

    /**
     * Gets the request object needed to make a next|previous page request.
     *
     * @param string $direction The direction of the page: next|previous
     *
     * @throws SDKException
     *
     * @return null|Request
     */
    public function getPaginationRequest($direction)
    {
        $pageUrl = $this->getPaginationUrl($direction);
        if (!$pageUrl) {
            return null;
        }

        $newRequest = clone $this->request;
        $newRequest->setEndpoint($pageUrl);

        return $newRequest;
    }

    /**
     * Gets the request object needed to make a "next" page request.
     *
     * @throws SDKException
     *
     * @return null|Request
     */
    public function getNextPageRequest()
    {
        return $this->getPaginationRequest('next');
    }

    /**
     * Gets the request object needed to make a "previous" page request.
     *
     * @throws SDKException
     *
     * @return null|Request
     */
    public function getPreviousPageRequest()
    {
        return $this->getPaginationRequest('previous');
    }

    /**
     * The total number of results according to Graph if it exists.
     *
     * This will be returned if the summary=true modifier is present in the request.
     *
     * @return null|int
     */
    public function getTotalCount()
    {
        if (isset($this->metaData['summary']['total_count'])) {
            return $this->metaData['summary']['total_count'];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function map(\Closure $callback)
    {
        return new static(
            $this->request,
            array_map($callback, $this->items, array_keys($this->items)),
            $this->metaData,
            $this->parentEdgeEndpoint,
            $this->subclassName
        );
    }
}
