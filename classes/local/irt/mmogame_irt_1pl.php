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
     * @param int $maxiter
     * @return array
     */
    public static function compute(array $responses, int $numitems, int $maxiter = 150): array {
        $numstudents = count($responses);

        // Initialize theta and b.
        $theta = array_fill(0, $numstudents, 0.0);
        $b = array_fill(0, $numitems, 0.0);

        // JMLE estimation.
        for ($iter = 0; $iter < $maxiter; $iter++) {
            // Update item difficulties b_j.
            for ($j = 0; $j < $numitems; $j++) {
                $num = 0.0;
                $den = 0.0;
                for ($i = 0; $i < $numstudents; $i++) {
                    $x = $responses[$i][$j];
                    if ($x === null) {
                        continue;
                    }
                    $p = 1 / (1 + exp(-($theta[$i] - $b[$j])));
                    $num += $x - $p;
                    $den += $p * (1 - $p);
                }
                if ($den != 0) {
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
                    $num += $x - $p;
                    $den += $p * (1 - $p);
                }
                if ($den != 0) {
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

        foreach ($theta as &$thetaj) {
            if ($thetaj > -6 && $thetaj < 6) {
                $thetaj -= $meanb;
            }
        }

        // Computes SE for b.
        $seb = $freq = $percent = [];
        for ($j = 0; $j < $numitems; $j++) {
            $suminfo = 0.0;
            $count0 = $count1 = $countnull = 0;
            for ($i = 0; $i < $numstudents; $i++) {
                $x = $responses[$i][$j];

                if ($x === null) {
                    $countnull++;
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
            $seb[$j] = ($suminfo > 0) ? 1 / sqrt($suminfo) : null;
            $freq[$j] = "$count1-$count0-$countnull";

            $percent[$j] = $count0 + $count1 === 0 ? null : $count1 / ($count0 + $count1) * 100;
        }

        // Improved Infit and Outfit.
        $infit = [];
        $outfit = [];
        for ($j = 0; $j < $numitems; $j++) {
            $sumwz2 = 0.0;
            $sumw = 0.0;
            $sumz2 = 0.0;
            $count = 0;

            for ($i = 0; $i < $numstudents; $i++) {
                $x = $responses[$i][$j];
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

            $infit[$j] = ($sumw > 0) ? $sumwz2 / $sumw : null;
            $outfit[$j] = ($count > 0) ? $sumz2 / $count : null;
        }

        self::compute_std_fit($numitems, $numstudents, $responses, $infit, $outfit, $stdinfit, $stdoutfit);

        return [
            'theta' => $theta,
            'b' => $b,
            'se_b' => $seb,
            'infit' => $infit,
            'outfit' => $outfit,
            'std_infit' => $stdinfit,
            'std_outfit' => $stdoutfit,
            'freq' => $freq,
            'percent' => $percent,
        ];
    }

    /**
     * Computes std fit
     *
     * @param int $numitems
     * @param int $numstudents
     * @param array $responses
     * @param array $infit
     * @param array $outfit
     * @param ?array $stdinfit
     * @param ?array $stdoutfit
     * @return void
     */
    protected static function compute_std_fit(int $numitems, int $numstudents, array $responses,
                array $infit, array $outfit, ?array &$stdinfit, ?array &$stdoutfit): void {
        $stdinfit = [];
        $stdoutfit = [];

        for ($j = 0; $j < $numitems; $j++) {
            $count = 0;
            for ($i = 0; $i < $numstudents; $i++) {
                $x = $responses[$i][$j];
                if ($x !== null) {
                    $count++;
                }
            }

            $df = $count;

            if ($df > 0) {
                // Variance approximation for small sample size.
                $varinfit = 2 / $df;
                $varoutfit = 2 / $df;

                $stdinfit[$j] = ($infit[$j] - 1) / sqrt($varinfit);
                $stdoutfit[$j] = ($outfit[$j] - 1) / sqrt($varoutfit);
            } else {
                $stdinfit[$j] = null;
                $stdoutfit[$j] = null;
            }
        }
    }

    /**
     * Saves computations on database.
     *
     * @param mmogame $mmogame
     * @param array $data
     * @param array $mapusers
     * @return void
     * @throws \dml_exception
     */
    public static function save(mmogame $mmogame, array $data, array $mapusers): void {
        global $DB, $USER;

        $rec = $DB->get_record_select( 'mmogame_aa_irt_key',
            'mmogameid=? AND numgame=? AND userid=?',
            [$mmogame->get_id(), $mmogame->get_numgame(), $USER->id]);
        if (!$rec) {
            $rec = new stdClass();
            $rec->mmogameid = $mmogame->get_id();
            $rec->numgame = $mmogame->get_numgame();
            $rec->userid = $USER->id;
            $rec->timecreated = time();
            $keyid = $DB->insert_record( 'mmogame_aa_irt_key', $rec);
        } else {
            $keyid = $rec->id;
            $rec = new stdClass();
            $rec->id = $keyid;
            $rec->timecreated = time();
            $DB->update_record('mmogame_aa_irt_key', $rec);
        }

        $DB->delete_records_select('mmogame_aa_irt_questions', 'keyid=?', [$keyid]);
        $DB->delete_records_select('mmogame_aa_irt_students', 'keyid=?', [$keyid]);

        $b = $data['b'];
        $seb = $data['se_b'];
        $infit = $data['infit'];
        $outfit = $data['outfit'];
        $stdinfit = $data['std_infit'];
        $stdoutfit = $data['std_outfit'];
        $freq = $data['freq'];
        $percent = $data['percent'];

        foreach( $b as $key => $vb) {
            $new = new stdClass();
            $new->keyid = $keyid;
            $new->keyrec = $key;
            $new->b = $vb;
            $new->se_b = $seb[$key];
            $new->infit = $infit[$key];
            $new->std_infit = $stdinfit[$key];
            $new->outfit = $outfit[$key];
            $new->std_outfit = $stdoutfit[$key];
            $new->freq = $freq[$key];
            $new->percent = $percent[$key];

            $DB->insert_record('mmogame_aa_irt_questions', $new);
        }

        $keys = array_keys( $mapusers);
        $theta = $data['theta'];
        $pos = 0;
        foreach( $theta as $key => $vtheta) {
            $user = $mapusers[ $keys[$pos++]];
            $new = new stdClass();
            $new->keyid = $keyid;
            $new->keyrec = $key;
            $new->mmogameid = $user->mmogameid;
            $new->numgame = $user->numgame;
            $new->auserid = $user->auserid;
            $new->theta = $vtheta;
            $DB->insert_record('mmogame_aa_irt_students', $new);
        }
    }
}
