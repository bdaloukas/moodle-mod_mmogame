<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the IRT 1PL implemenation
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mmogame\local\irt;

use dml_exception;
use mod_mmogame\local\mmogame;
use stdClass;

/**
 * The class mmogame_irt_1pl has the code for IRT 1PL analysis
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mmogame_irt_1pl {
    /**
     * Do computation.
     *
     * @param array $responses
     * @param int $numitems
     * @param array $irtq
     * @param array $irtu
     * @param int $maxiter
     * @return void
     */
    public static function compute(array $responses, int $numitems, array &$irtq, array &$irtu, int $maxiter = 150): void {
        $numstudents = count($responses);

        $irtq = $irtu = [];

        // Initialize theta and b.
        $theta = array_fill(0, $numstudents, 0.0);
        $b = array_fill(0, $numitems, 0.0);

        // Early-stop parameters.
        $tol = 1e-4;   // Max absolute change threshold.
        $patience = 3; // Require < tol for this many consecutive iterations.
        $stable   = 0;

        // JMLE estimation.
        for ($iter = 0; $iter < $maxiter; $iter++) {
            // Keep previous values for early-stop check.
            $bprev = $b;
            $thetaprev = $theta;

            // Update item difficulties b_j.
            for ($j = 0; $j < $numitems; $j++) {
                $num = 0.0;
                $den = 0.0;
                for ($i = 0; $i < $numstudents; $i++) {
                    $x = $responses[$i][$j];
                    // The x is 0/1/null.
                    if ($x === null) {
                        continue;
                    }
                    $p = 1 / (1 + exp(-($theta[$i] - $b[$j])));
                    $num += $x - $p;
                    $den += $p * (1 - $p);
                }
                if ($den > 1e-8) {
                    $b[$j] -= $num / $den;
                }
            }

            // Apply bounding to b to avoid extreme values.
            foreach ($b as &$bj) {
                if ($bj > 4) {
                    $bj = 4;
                } else if ($bj < -4) {
                    $bj = -4;
                }
            }
            unset($bj);

            // Update abilities theta_i.
            for ($i = 0; $i < $numstudents; $i++) {
                $num = 0.0;
                $den = 0.0;
                for ($j = 0; $j < $numitems; $j++) {
                    $x = $responses[$i][$j];
                    if ($x === null) {
                        continue;
                    }
                    $p = 1 / (1 + exp(-($theta[$i] - $b[$j])));
                    $num += (int )$x - $p;
                    $den += $p * (1 - $p);
                }
                if ($den > 1e-8) {
                    $theta[$i] += $num / $den;
                }
            }

            foreach ($theta as &$thetaj) {
                if ($thetaj > 6) {
                    $thetaj = 6;
                } else if ($thetaj < -6) {
                    $thetaj = -6;
                }
            }

            // Early stop: max parameter change across b and theta.
            $maxdelta = 0.0;
            for ($j = 0; $j < $numitems; $j++) {
                $d = abs($b[$j] - $bprev[$j]);
                if ($d > $maxdelta) {
                    $maxdelta = $d;
                }
            }
            for ($i = 0; $i < $numstudents; $i++) {
                $d = abs($theta[$i] - $thetaprev[$i]);
                if ($d > $maxdelta) {
                    $maxdelta = $d;
                }
            }

            if ($maxdelta < $tol) {
                $stable++;
                if ($stable >= $patience) {
                    break; // Converged.
                }
            } else {
                $stable = 0;
            }
        }

        $bcount = $bsum = 0;
        foreach ($b as $bj) {
            if ($bj > -4 && $bj < 4) {
                $bcount++;
                $bsum += $bj;
            }
        }
        unset($bj);
        $meanb = $bcount > 0 ? $bsum / $bcount : 0;

        // Apply bounding to b to avoid extreme values.
        foreach ($b as &$bj) {
            if ($bj > 4) {
                $bj = 4;
            } else if ($bj < -4) {
                $bj = -4;
            } else {
                // Center b.
                $bj -= $meanb;
            }
        }
        unset($bj);

        // Centering theta.
        foreach ($theta as &$thetaj) {
            if ($thetaj > -6 && $thetaj < 6) {
                $thetaj -= $meanb;
            }
        }
        unset( $thetaj);

        // Computes SE for b.
        for ($j = 0; $j < $numitems; $j++) {
            $suminfo = 0.0;
            $count0 = $count1 = $countnullvalue = 0;
            for ($i = 0; $i < $numstudents; $i++) {
                $x = $responses[$i][$j];

                // The x is 0/1/null.
                if ($x === null) {
                    $countnullvalue++;
                    continue;
                }

                if ( $x == 0) {
                    $count0++;
                } else if ($x == 1) {
                    $count1++;
                }
                $p = 1 / (1 + exp(-($theta[$i] - $b[$j])));
                $suminfo += $p * (1 - $p);
            }
            $info = new stdClass();
            $info->b = $b[$j];
            $info->seb = ($suminfo > 0) ? 1 / sqrt($suminfo) : null;
            $info->corrects = $count1;
            $info->wrongs = $count0;
            $info->nulls = $countnullvalue;
            $info->percent = $count0 + $count1 === 0 ? null : $count1 / ($count0 + $count1) * 100;
            $irtq[] = $info;
            unset( $info);
        }

        // Improved Infit and Outfit.
        for ($j = 0; $j < $numitems; $j++) {
            $sumwz2 = 0.0;
            $sumw = 0.0;
            $sumz2 = 0.0;
            $count = 0;

            for ($i = 0; $i < $numstudents; $i++) {
                $x = $responses[$i][$j];
                // The x is 0/1/null.
                if ($x === null) {
                    continue;
                }

                $p = 1 / (1 + exp(-($theta[$i] - $b[$j])));
                $var = $p * (1 - $p);
                if ($var == 0) {
                    continue;
                }

                $z = ($x - $p) / sqrt($var);
                $sumwz2 += $var * $z * $z;
                $sumw += $var;
                $sumz2 += $z * $z;
                $count++;
            }
            $info = &$irtq[$j];
            $info->infit = ($sumw > 0) ? $sumwz2 / $sumw : null;
            $info->outfit = ($count > 0) ? $sumz2 / $count : null;
            unset( $info);
        }

        self::compute_std_fit($numitems, $numstudents, $responses, $irtq);
        foreach ($theta as $position => $thetaj) {
            $info = new stdClass();
            $info->theta = $thetaj;
            $irtu[$position] = $info;
        }
    }

    /**
     * Computes std fit
     *
     * @param int $numitems
     * @param int $numstudents
     * @param array $responses
     * @param array $irtq
     * @return void
     */
    protected static function compute_std_fit(int $numitems, int $numstudents, array $responses, array &$irtq): void {
        for ($j = 0; $j < $numitems; $j++) {
            $info = &$irtq[$j];

            $count = 0;
            for ($i = 0; $i < $numstudents; $i++) {
                $x = $responses[$i][$j] ?? null;
                if ($x !== null) {
                    $count++;
                }
            }

            $df = $count - 1;
            if ($df > 0) {
                // Variance approximation for small sample size.
                $varinfit = 2 / $df;
                $varoutfit = 2 / $df;

                $info->stdinfit = ($info->infit - 1) / sqrt($varinfit);
                $info->stdoutfit = ($info->outfit - 1) / sqrt($varoutfit);
            } else {
                $info->stdinfit = null;
                $info->stdoutfit = null;
            }
        }
    }

    /**
     * Compute id used for saving.
     *
     * @param mmogame $mmogame
     * @return int
     * @throws dml_exception
     */
    public static function keyid(mmogame $mmogame): int {
        global $DB, $USER;

        $rec = $DB->get_record_select( 'mmogame_aa_irt_key',
            'mmogameid=? AND numgame=? AND userid=?',
            [$mmogame->get_id(), $mmogame->get_numgame(), $USER->id]);
        if (!$rec) {
            $rec = new stdClass();
            $rec->mmogameid = $mmogame->get_id();
            $rec->numgame = $mmogame->get_numgame();
            $rec->userid = $USER->id;
            $rec->timecomputed = time();
            return $DB->insert_record( 'mmogame_aa_irt_key', $rec);
        }

        $upd = new stdClass();
        $upd->id = $rec->id;
        $upd->timecomputed = time();
        $DB->update_record('mmogame_aa_irt_key', $upd);

        return $upd->id;
    }

    /**
     * Saves computations on database.
     *
     * @param int $keyid
     * * @param array $irtq // indexed by item position (0..numitems-1)
     * * @param array $irtu // indexed by user position (0..numusers-1)
     * * @param array $mapqueries // map: queryKey => object { position, queryid, name, querytext, b_online|difficulty, ... }
     * * @param array $mapusers // map: userKey  => object { position, mmogameid, numgame, auserid, theta_online, corrects, ... }
     * @return void
     * @throws dml_exception
     */
    public static function save(int $keyid, array $irtq, array $irtu, array $mapqueries, array $mapusers): void {
        global $DB;

        $positionsq = array_keys($mapqueries);
        $positionsu = array_keys($mapusers);

        // Start atomic transaction (ensures delete+bulk insert consistency).
        $tx = $DB->start_delegated_transaction();

        // Clean previous rows for this key.
        $DB->delete_records_select('mmogame_aa_irt_queries', 'keyid=?', [$keyid]);
        $DB->delete_records_select('mmogame_aa_irt_ausers', 'keyid=?', [$keyid]);

        $pos = 0;
        foreach ($irtq as $irt) {
            $queryid = $positionsq[$pos++];
            $query = $mapqueries[$queryid];
            $new = new stdClass();
            $new->keyid = $keyid;
            $new->position = $query->position;
            $new->queryid = $query->queryid;
            $new->name = $query->name;
            $new->querytext = $query->querytext;
            $new->b = $irt->b;
            $new->b_online = $query->b_online;
            $new->seb = $irt->seb;
            $new->infit = $irt->infit;
            $new->std_infit = $irt->stdinfit;
            $new->outfit = $irt->outfit;
            $new->std_outfit = $irt->stdoutfit;
            $new->corrects = $irt->corrects;
            $new->wrongs = $irt->wrongs;
            $new->nulls = $irt->nulls;
            $new->percent = $irt->percent;

            $DB->insert_record('mmogame_aa_irt_queries', $new);
        }

        $pos = 0;
        foreach ($irtu as $irt) {
            $key = $positionsu[$pos++];
            $user = $mapusers[$key];
            $new = new stdClass();
            $new->keyid = $keyid;
            $new->position = $user->position;
            $new->mmogameid = $user->mmogameid;
            $new->numgame = $user->numgame;
            $new->auserid = $user->auserid;
            $new->theta = $irt->theta;
            $new->theta_online = $user->theta_online;
            $new->queries = $user->count;
            $new->corrects = $user->corrects;
            $new->wrongs = $user->wrongs;
            $new->nulls = count($mapqueries) - $user->count;
            $new->percent = ($user->count != 0 ? 100 * $user->corrects / $user->count : null);
            $DB->insert_record('mmogame_aa_irt_ausers', $new);
        }

        // Commit (will auto-rollback on exception).
        $tx->allow_commit();
    }
}
