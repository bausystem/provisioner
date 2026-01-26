<?php

namespace Bausystem\Provisioner;

use Blocks\ErrorHandler\Exception\ExceptionWithData;
use Assert\Assert;

class ConfigFile {

    public static function addSection(string $filepath, string $tag, string $text) {
        // Validate inputs
        Assert::that($filepath)->notEmpty()->string("Filepath must be a non-empty string.");
        Assert::that($tag)->notEmpty()->string("Tag must be a non-empty string.");
        Assert::that($text)->string("Text must be a string.");

        if ( !is_file($filepath) ) {
            throw new ExceptionWithData("File does not exist",
                [ 'filepath' => $filepath ]  
            );
        }

        if ( !is_writable($filepath) ) {
            throw new ExceptionWithData("File is not writable",
                [ 'filepath' => $filepath ]  
            );
        }

        $tag = self::getCorrectedTag($tag);

        $beginTag = "#BEGIN_$tag";
        $endTag = "#END_$tag";

        // Check if the tag already exists in the file
        if (file_exists($filepath)) {
            $fileContent = file_get_contents($filepath);
            if (strpos($fileContent, $beginTag) !== false || strpos($fileContent, $endTag) !== false) {
                throw new \RuntimeException("The tag '$tag' already exists in the file.");
            }
        }

        // Check if the file ends with a newline character
        $addNewline = true;

        if (file_exists($filepath)) {
            $fileSize = filesize($filepath);
            if ($fileSize > 0) {
                $handle = fopen($filepath, 'r');
                fseek($handle, -1, SEEK_END); // Move to the last character
                $lastChar = fgetc($handle);
                fclose($handle);

                if ($lastChar === "\n") {
                    $addNewline = false; // File already ends with a newline
                }
            } else {
                $addNewline = false; // Empty file, no newline needed
            }
        }

        // Open the file in append mode
        $handle = fopen($filepath, 'a');

        if ($handle) {
            // Add a newline if needed
            if ($addNewline) {
                fwrite($handle, "\n");
            }

            // Construct the section
            $section = "$beginTag\n";
            $section .= $text . "\n";
            $section .= "$endTag\n";

            // Write the section to the file
            fwrite($handle, $section);

            fclose($handle);
        } else {
            throw new \RuntimeException("Unable to open the file for writing.");
        }

        return true;
    }

    public static function removeSection(string $filepath, string $tag) {
        // Validate inputs
        Assert::that($filepath)->notEmpty()->string("Filepath must be a non-empty string.");
        Assert::that($tag)->notEmpty()->string("Tag must be a non-empty string.");

        if ( !is_file($filepath) ) {
            throw new ExceptionWithData("File does not exist",
                [ 'filepath' => $filepath ]  
            );
        }

        if ( !is_writable($filepath) ) {
            throw new ExceptionWithData("File is not writable",
                [ 'filepath' => $filepath ]  
            );
        }

        $tag = self::getCorrectedTag($tag);

        $beginTag = "#BEGIN_$tag";
        $endTag = "#END_$tag";

        // Read the file content
        $fileContent = file_get_contents($filepath);

        // Find the section boundaries
        $beginPos = strpos($fileContent, $beginTag);
        $endPos = strpos($fileContent, $endTag);

        if ($beginPos === false || $endPos === false) {
            throw new \RuntimeException("The tag '$tag' does not exist in the file.");
        }

        // Ensure the section includes the full line of the tags
        $endPos += strlen($endTag) + 1; // Include the newline after the end tag

        // Remove the section
        $updatedContent = substr($fileContent, 0, $beginPos) 
                        . substr($fileContent, $endPos);

        // Write the updated content back to the file
        file_put_contents($filepath, $updatedContent);

        return true;
    }

    private static function getCorrectedTag(string $tag) {
        $corrected_tag = preg_replace('/[^A-Z0-9]/', '_', mb_strtoupper($tag));

        return $corrected_tag;
    }

}
