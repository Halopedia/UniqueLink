<?php

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file Hooks.php
 * @author Daniel Beard
 */

namespace MediaWiki\Extension\UniqueLink;

/**
 * A class wrapping a store of pages that have previously been linked to, as
 * well as functions for resetting that store and handling related parser
 * function calls.
 */
class LinkStore {

  /**
   * A private variable for storing unique links that have been made but which
   * weren't categorised.
   */
  private $uncategorisedLinks = [];

  /**
   * A private variable for storing unique links that have been made in
   * specific categories.
   */
  private $categorisedLinks = [];

  /**
   * A function used to check if a given page name has already been linked to
   * 
   * @param $name the page to check
   * @param $category the category to check in 
   */
  private function alreadyLinked( $name, $category = '' ) : bool {
    if ( empty( $category ) ) {
      // If the category is empty, check the uncategorised link list.
      return in_array( $name, $this->uncategorisedLinks );
    } else {
      // Otherwise, check the categorised lin list
      return !empty( $this->categorisedLinks[ $category] )
        && in_array( $name, $this->categorisedLinks[ $category ] );
    }
  }

  /**
   * A function used to mark a given page as having been linked to.
   * 
   * @param $name the page to mark as linked
   * @param $category the category to mark the page as linked in 
   */
  private function setLinked( $name, $category = '' ) : void {
    if ( empty( $category ) ) {
      // If the category is empty, add the page to the uncategorised link list
      array_push( $this->uncategorisedLinks, $name );
    } else {
      // Otherwise, add it to the relevant subarray in the categorised link
      // array, creating that array if it doesn't already exist 
      if ( empty( $this->categorisedLinks[ $category ] ) ) {
        $this->categorisedLinks[ $category ] = [];
      }
      array_push( $this->categorisedLinks[ $category ], $name );
    }
  }

  /**
   * A function used to reset the link store for a parser, so it can parse a
   * different page.
   * 
   * @param \Parser $parser the parser to reset the link store of
   */
  public static function resetLinkStore( \Parser $parser ) : void {
    $parser->extUniqueLinkStore = new LinkStore();
  }

  /**
   * A function used to fetch the link store from a parser.
   * 
   * @param \Parser $parser the parser whose link store to fetch
   */
  private static function getLinkStore( \Parser $parser ) : LinkStore {
    return $parser->extUniqueLinkStore;
  }

  /**
   * The function that is used to process calls to the uniquelink parser
   * function.
   * 
   * @param \Parser $parser the parser currently parsing the relevant page
   * @param $dest the name of the page to link to
   * @param $text the text to display in the link body
   * @param $category the category to record the link in
   * 
   * @return string the wiki markup for the link or just the text if applicable
   */
  public static function parserFunc_uniquelink(
    \Parser $parser, $dest, $text = '', $category = ''
  ) {
    // Do nothing if no dest supplied
    if ( empty( $dest ) ) {
      return;
    }

    // Retrieve the link store
    $store = self::getLinkStore( $parser );

    // Display the destination page name as the link text if no link text is
    // specified 
    $text = empty( $text ) ? $dest : $text;

    if ( $store->alreadyLinked( $dest, $category ) ) {
      // Just return the text if dest has already been linked to
      return $text;
    } else {
      // Update the linked store and display the link if it has not been
      $store->setLinked( $dest, $category );
      return "[[$dest|$text]]";
    }
  }

  /**
   * The function that is used to process calls to the uniquelinkifexists
   * parser function.
   * 
   * @param \Parser $parser the parser currently parsing the relevant page
   * @param $dest the name of the page to link to
   * @param $text the text to display in the link body
   * @param $category the category to record the link in
   * 
   * @return string the wiki markup for the link or just the text if applicable
   */
  public static function parserFunc_uniquelinkifexists(
    \Parser $parser, $dest, $text = '', $category = ''
  ) {
    // Do nothing if no dest supplied
    if ( empty( $dest ) ) {
      return;
    }

    // Retrieve the link store
    $store = self::getLinkStore( $parser );

    // Display the destination page name as the link text if no link text is
    // specified 
    $text = empty( $text ) ? $dest : $text;

    if ( $store->alreadyLinked( $dest, $category ) ) {
      // Just return the text if dest has already been linked to
      return $text;
    } else {
      // If it has not been, update the link store and then check whether dest
      // actually exists
      $store->setLinked( $dest, $category );
      $title = \Title::newFromText( $dest );
      if ( $title->isExternal() || $title->exists() ) {
        return "[[$dest|$text]]";
      } else {
        return $text;
      }
    }
  }

  /**
   * The function that is used to process calls to the alreadylinkeduniquely
   * parser function.
   * 
   * @param \Parser $parser the parser currently parsing the relevant page
   * @param \PPFrame $frame the parser frame currently being processed
   * @param array $args the arguments passed 
   * 
   * @return string the wiki markup to be parsed and displayed on the page
   */
  public static function parserFuncObj_alreadylinkeduniquely(
    \Parser $parser, \PPFrame $frame, array $args
  ) {
    // Expand dest and category
    $dest = empty( $args[0] ) ? '' : trim( $frame->expand( $args[0] ) );
    $category = empty( $args[1] ) ? '' : trim( $frame->expand( $args[1] ) );

    // Retrieve the link store
    $store = self::getLinkStore( $parser );

    if ( !empty( $dest ) && $store->alreadyLinked( $dest, $category ) ) {
      // If the dest is in the link store, expand and display arg 2 (if it
      // exists) or just display '1'
      return empty( $args[2] ) ? '1' : trim( $frame->expand( $args[2] ) );
    } else {
      // Otherwise, expand and display arg 3 (if it exists) or display nothing
      return empty( $args[3] ) ? '' : trim( $frame->expand( $args[3] ) );
    }

  }

}

?>