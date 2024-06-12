<?php
	include 'includes/session.php';
	include 'includes/slugify.php';

	$sql = "SELECT * FROM positions";
	$pstmt = $conn->prepare($sql);
	$pstmt->execute();
	$pquery = $pstmt->get_result();

	$output = '';
	$candidate = '';

	$sql = "SELECT * FROM positions ORDER BY priority ASC";
	$stmt = $conn->prepare($sql);
	$stmt->execute();
	$result = $stmt->get_result();
	$num = 1;
	while($row = $result->fetch_assoc()){
		$input = ($row['max_vote'] > 1) ? '<input type="checkbox" class="flat-red '.slugify($row['description']).'" name="'.slugify($row['description'])."[]".'">' : '<input type="radio" class="flat-red '.slugify($row['description']).'" name="'.slugify($row['description']).'">';

		$sql = "SELECT candidates.*, partylists.name AS partylist_name 
        FROM candidates 
        LEFT JOIN partylists ON candidates.partylist_id = partylists.id 
        WHERE position_id=?";
		$cstmt = $conn->prepare($sql);
		$cstmt->bind_param("i", $row['id']);
		$cstmt->execute();
		$cquery = $cstmt->get_result();
		while($crow = $cquery->fetch_assoc()){
			$image = (!empty($crow['photo'])) ? '../images/'.$crow['photo'] : '../images/profile.jpg';
			$candidate .= '
				<li>
					'.$input.'<button class="btn btn-primary btn-sm btn-flat clist"><i class="fa fa-search"></i> Platform</button><img src="'.$image.'" height="100px" width="100px" class="clist"><span class="cname clist">'.$crow['firstname'].' '.$crow['lastname'].' — '.$crow['partylist_name'].'</span>
				</li>
			';
		}

		$instruct = ($row['max_vote'] > 1) ? 'You may select up to '.$row['max_vote'].' candidates' : 'Select only one candidate';
		
		$updisable = ($row['priority'] == 1) ? 'disabled' : '';
		$downdisable = ($row['priority'] == $pquery->num_rows) ? 'disabled' : '';

		$output .= '
			<div class="row">
				<div class="col-xs-12">
					<div class="box box-solid" id="'.$row['id'].'">
						<div class="box-header with-border">
							<h3 class="box-title"><b>'.$row['description'].'</b></h3>
							<div class="pull-right box-tools">
				                <button type="button" class="btn btn-default btn-sm moveup" data-id="'.$row['id'].'" '.$updisable.'><i class="fa fa-arrow-up"></i> </button>
				                <button type="button" class="btn btn-default btn-sm movedown" data-id="'.$row['id'].'" '.$downdisable.'><i class="fa fa-arrow-down"></i></button>
				            </div>
						</div>
						<div class="box-body">
							<p>'.$instruct.'
								<span class="pull-right">
									<button type="button" class="btn btn-success btn-sm btn-flat reset" data-desc="'.slugify($row['description']).'"><i class="fa fa-refresh"></i> Reset</button>
								</span>
							</p>
							<div id="candidate_list">
								<ul>
									'.$candidate.'
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		';

		$sql = "UPDATE positions SET priority = ? WHERE id = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ii", $num, $row['id']);
		$stmt->execute();

		$num++;
		$candidate = '';
	}

	echo json_encode($output);
