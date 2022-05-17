<?php namespace System\Console;

use Db;
use Schema;
use Exception;

/**
 * OctoberUtilPatches is a dedicated class for version patches
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
trait OctoberUtilPatches
{
    /**
     * utilPatch2Point0 october:util patch2.0
     */
    protected function utilPatch2Point0()
    {
        if (!$this->confirmToProceed('This will patch your database for October CMS 2.0. Please make sure you have a backup before proceeding.')) {
            return;
        }

        $this->output->newLine();
        $this->comment('*** Patching DEFERRED BINDINGS table');

        if (Schema::hasColumn('deferred_bindings', 'str_slave_id')) {
            $this->comment('Patch already applied to schema');
        }
        else {
            try {
                $this->comment('Cleaning up indexes');
                Schema::table('deferred_bindings', function($table) {
                    $table->dropIndex(['slave_type']);
                });
            }
            catch (Exception $ex){}
            try {
                Schema::table('deferred_bindings', function($table) {
                    $table->dropIndex(['slave_id']);
                });
            }
            catch (Exception $ex){}
            try {
                Schema::table('deferred_bindings', function($table) {
                    $table->dropIndex(['session_key']);
                });
            }
            catch (Exception $ex){}

            Db::transaction(function() {
                $this->comment('Optimizing columns');
                Schema::table('deferred_bindings', function($table) {
                    $table->renameColumn('slave_id', 'str_slave_id');
                });

                Schema::table('deferred_bindings', function($table) {
                    $table->string('str_slave_id')->nullable()->change();
                });

                Schema::table('deferred_bindings', function($table) {
                    $table->integer('slave_id')->after('slave_type')->nullable();
                });
            });
        }

        $this->output->newLine();
        $this->comment('*** Transferring DEFERRED BINDINGS data');

        $failedRows = [];
        Db::table('deferred_bindings')->whereNull('slave_id')->orderBy('id')
            ->chunkById(100, function($bindings) use (&$failedRows) {
                foreach ($bindings as $binding) {
                    if (is_null($binding->str_slave_id)) {
                        // Field is already null
                    }
                    elseif (!is_numeric($binding->str_slave_id)) {
                        $failedRows[] = $binding->id;
                        $this->output->write('!', false);
                    }
                    else {
                        Db::table('deferred_bindings')
                            ->where('id', $binding->id)
                            ->update(['slave_id' => (int) $binding->str_slave_id])
                        ;
                        $this->output->write('.', false);
                    }
                }
            });

        $this->comment('Transfer complete');

        if (count($failedRows) > 0) {
            $this->output->newLine();
            $this->warn('Warning! String values detected for DEFERRED BINDINGS rows:');
            $this->warn(sprintf('[%s]', implode(' ', $failedRows)));
            $this->warn('You must address these columns manually, they have not been transferred.');
            $this->warn('Contact support if you require assistance. Copy these numbers down and do not lose this list.');
            $this->output->newLine();
        }

        $this->output->newLine();
        $this->comment('*** Patching SYSTEM FILES table');

        if (Schema::hasColumn('system_files', 'str_attachment_id')) {
            $this->comment('Patch already applied to schema');
        }
        else {
            try {
                $this->comment('Cleaning up indexes');
                Schema::table('system_files', function($table) {
                    $table->dropIndex(['attachment_id']);
                });
            }
            catch (Exception $ex){}
            try {
                Schema::table('system_files', function($table) {
                    $table->dropIndex(['attachment_type']);
                });
            }
            catch (Exception $ex){}

            Db::transaction(function() {
                $this->comment('Optimizing columns');
                Schema::table('system_files', function($table) {
                    $table->renameColumn('attachment_id', 'str_attachment_id');
                });

                Schema::table('system_files', function($table) {
                    $table->string('str_attachment_id')->nullable()->change();
                });

                Schema::table('system_files', function($table) {
                    $table->integer('attachment_id')->after('field')->nullable();
                });

                Schema::table('system_files', function($table) {
                    $table->index(['attachment_id', 'attachment_type'], 'system_files_master_index');
                });
            });
        }

        $this->output->newLine();
        $this->comment('*** Transferring SYSTEM FILES data');

        $failedRows = [];
        Db::table('system_files')->whereNull('attachment_id')->orderBy('id')
            ->chunkById(100, function($files) use (&$failedRows) {
                foreach ($files as $file) {
                    if (is_null($file->str_attachment_id)) {
                        // Field is already null
                    }
                    elseif (!is_numeric($file->str_attachment_id)) {
                        $failedRows[] = $file->id;
                        $this->output->write('!', false);
                    }
                    else {
                        Db::table('system_files')
                            ->where('id', $file->id)
                            ->update(['attachment_id' => (int) $file->str_attachment_id])
                        ;
                        $this->output->write('.', false);
                    }
                }
            });

        $this->comment('Transfer complete');

        if (count($failedRows) > 0) {
            $this->output->newLine();
            $this->warn('Warning! String values detected for SYSTEM FILES rows:');
            $this->warn(sprintf('[%s]', implode(' ', $failedRows)));
            $this->warn('You must address these columns manually, they have not been transferred.');
            $this->warn('Contact support if you require assistance. Copy these numbers down and do not lose this list.');
            $this->output->newLine();
        }

        $this->output->success('October CMS Version 2.0 applied!');
    }
}
