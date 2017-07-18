<?php
/*
Plugin Name:CustumFieldSearch
Plugin URI:http://creative-studio.jp/site/cfs/
Description:custmu filed search plugin
Version:1.0
Author:custmu filed search plugin
Author URI:http://creative-studio.jp/company
*/

/*
 * カスタムフィールドをAND検索する為のプラグインです。
 *
 * プラグインを有効化すれば検索画面に自動でカスタムフィールド検索結果を表示します。
 *
 * その他の埋め込み式の使い方は
 * 使い方は投稿内に
 * [CFS]
 * と入力する事で動作します。
 *
 * 検索方法はカスタムフィールドは全て「postmeta」テーブルに格納されるので、postmetaテーブルを検索している。
 *
*/



//　ショートコードの設定
function shortcodeCFS() {
 CFS();
}
add_shortcode('CFS', 'shortcodeCFS');

add_action( 'get_footer', 'CFS' );




function CFS()
{


global $wpdb;
$search = $_GET['s'];


//　wordpressの検索画面になったら表示する
if($search)
{


	?>
	<style>
	#cfs {width:50%;margin:auto;}
	#cfs li {border-bottom:1px solid #ccc;padding:10px;margin:0 0 10px;list-style:none;}
	#cfs img {width:50px;height:50px;margin:0 10px 0 0;}
	</style>
	<?php
	
	


	//　取得した検索ワードを空白で切り配列に入れる  ---------------------------------------- //
	$search = mb_convert_kana($search, 's', 'UTF-8');
	$search = preg_split('/[\s]+/', $search, -1, PREG_SPLIT_NO_EMPTY);



	/***************************************************************************************************
	*　検索ワードの中にカテゴリ名があったら重複してるカテゴリ内の投稿IDだけを取り出す 
	***************************************************************************************************/

	//　カテゴリ名と同じ検索ワードがあったら配列$termidに入れる  -----------------------------  //
	$i = 0;
	foreach($search as $searchs)
	{

		//　termsテーブルからカテゴリIDの取得
		$cate = $wpdb->get_results("SELECT * FROM $wpdb->terms where name LIKE '%$searchs%'");

		//　カテゴリ名以外の検索ワードだったら配列$chkWordに入れる
		if(count($cate) == 0)
		{
			$chkWord[] = $searchs;

		} else {

			$chkCateWord[] = $searchs;

		}



		//　検索ワードが同じなら同じ配列に入れる
		if(!$stock)
		{

			foreach($cate as $cates)
			{
				$termid[$i][] = $cates->term_id;
			}


		} else {

			if($stock != $searchs)
			{

				$i++;

			}


			foreach($cate as $cates)
			{

				$termid[$i][] = $cates->term_id;

			}


		}

		$stock = $searchs;


	}



	//　配列$termidからterm_relationshipsテーブルからカテゴリ内の投稿IDを配列$cateidに入れる  ---------------- //
	$loop = 0;
	foreach($termid as $termids)
	{

		foreach($termids as $key){



		$term = $wpdb->get_results("SELECT * FROM $wpdb->term_relationships where term_taxonomy_id = $key");
		foreach($term as $terms)
		{

			//　投稿IDがpublishかの判定
			$publish = $wpdb->get_var("SELECT post_status FROM $wpdb->posts where ID = $terms->object_id");
			if($publish == 'publish')
			{

				$cateid[$loop][] = $terms->object_id;

			}




		}

		}

		$loop++;

	}


	//　配列$cateidから投稿のないカテゴリは消す
	$i = 0;
	$j = 0;
	foreach($termid as $cateids)
	{
		if($cateid[$i] != null)
		{
			$category[$j] = $cateid[$i];
			$j++; 
		}
		$i++;
	}



	// 配列$categoryArrayにand検索のカテゴリ名が格納
	$i = 1;
	foreach($category as $categorys)
	{
		$j = $i - 1;
		$categoryArray = array_intersect( $category[$i],  $category[$j] );

	}
	



	/***************************************************************************************************
	*　検索ワードの中にカテゴリ名があったらカテゴリ内の投稿IDを抽出する 
	***************************************************************************************************/
	foreach($search as $searchs)
	{


		//　termsテーブルからカテゴリIDの取得
		$cate = $wpdb->get_results("SELECT * FROM $wpdb->terms where name LIKE '$searchs'");
		foreach($cate as $cates)
		{

			$termid[] = $cates->term_id;

		}


		//　term_relationshipsテーブルからカテゴリ内の投稿IDの取得
		foreach($termid as $termids)
		{


			$term = $wpdb->get_results("SELECT * FROM $wpdb->term_relationships where term_taxonomy_id = $termids");
			foreach($term as $terms)
			{


				//　投稿IDがpublishかの判定
				$publish = $wpdb->get_var("SELECT post_status FROM $wpdb->posts where ID = $terms->object_id");
				if($publish == 'publish')
				{

					$postid[] = $terms->object_id;

				}


			}


		}


	}



	/***************************************************************************************************
	*　投稿タイトルと本文内の検索
	***************************************************************************************************/
	foreach($search as $searchs)
	{


		//　postsテーブルからタイトルの検索
		$title = $wpdb->get_results("SELECT * FROM $wpdb->posts where post_title like '%$searchs%' and post_status = 'publish'");
		foreach($title as $titles)
		{


			$postid[] = $titles->ID;


		}


		//　postsテーブルから本文の検索
		$content = $wpdb->get_results("SELECT * FROM $wpdb->posts where post_content like '%$searchs%' and post_status = 'publish'");
		foreach($content as $contents)
		{


			$postid[] = $contents->ID;


		}


	}






	/***************************************************************************************************
	*　検索ワードが一つの処理
	***************************************************************************************************/
	if(count($search) == 1)
	{


		//　postmetaテーブルから検索ワードの該当するpostidの取得
		$result = $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta where meta_value LIKE '%$search[0]%'");
		foreach($result as $results)
		{

			$postid[] = $results->post_id;

		}

		
		// 重複してるidの削除
		$postid = array_unique($postid);




	/***************************************************************************************************
	*　検索ワードが複数の処理
	***************************************************************************************************/	
	} else {


		$i = 0;
		$extraction = array();
		foreach($search as $searchs)
		{


			//　postmetaテーブルから検索ワードの該当するpostidの取得
			$result = '';
			$result = $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta where meta_value LIKE '%$searchs%'");
			foreach($result as $results)
			{

				$extraction[$i][] = $results->post_id;

			}

			
			//　postsテーブルからタイトルの検索
			$title = $wpdb->get_results("SELECT * FROM $wpdb->posts where post_title like '%$searchs%' and post_status = 'publish'");
			foreach($title as $titles)
			{


				$extraction[$i][] = $titles->ID;


			}


			//　postsテーブルから本文の検索
			$content = $wpdb->get_results("SELECT * FROM $wpdb->posts where post_content like '%$searchs%' and post_status = 'publish'");
			foreach($content as $contents)
			{


				$extraction[$i][] = $contents->ID;


			}



			//　抽出した配列から重複してるAND検索のIDだけ取り出す
			$j = $i - 1;
			if($i > 0)
			{

				$postArray = array_intersect( $extraction[$i],  $extraction[$j]);	

			}


			$i++;
	

		}





		//　検索ワードにカテゴリ名以外があるか
		if($chkWord)
		{
		
			//　カテゴリ名以外ありカテゴリ名なしの検索の場合
			if(count($category) == 0)
			{

				$postid = $postArray;


			//　カテゴリ名ありの検索の場合
			} else {

				//　カテゴリが一つの場合
				if(count($category) == 1)
				{

					//　カテゴリが一つでワードが一つの場合
					if(count($extraction) == 1)
					{

						$extractionOne = array_unique($extraction[0]);
						$postid = array_intersect( $extractionOne,  $category[0]);	


					//　カテゴリが一つでワードが複数の場合
					} else {

						$postid = array_intersect( $postArray,  $category[0]);	

					}


				//　カテゴリが複数の場合
				} else {

					//　カテゴリが複数でワードが一つの場合
					if(count($extraction) == 1)
					{

						$extractionOne = array_unique($extraction[0]);
						$postid = array_intersect( $extractionOne,  $categoryArray);	


					//　カテゴリが複数でワードが複数の場合
					} else {

						$postid = array_intersect( $postArray,  $categoryArray);

					}

					
	
				}

		
			}
	

		//　検索ワードがカテゴリ名だけなら
		} else {

			$postid = $categoryArray;

		}


		// 重複してるidの削除
		$postid = array_unique($postid);


	}



	?>



	<div id="cfs">
	    <ul>
	<?php
	

	//　配列$postidからでpostsテーブルから情報取得・ループ表示
	foreach($postid as $postids)
	{

	
		//　タイトルの取得
		$title = $wpdb->get_var("SELECT post_title FROM $wpdb->posts where ID = $postids");

		//　カテゴリの取得
		$cat = get_the_category($postids);
		$cat = $cat[0];
		$cat = get_cat_name($cat->term_id);


		?>
		<li class="com-headline">
        		<a href="<?php bloginfo('url'); ?>/?p=<?php echo $postids; ?>">

			<?php
			//　サムネイルの判定・表示
			if(get_the_post_thumbnail($postids))
			{
 

				/*サムネイルの表示*/
				echo get_the_post_thumbnail($postids,array(100, 100));
 
			}else{
 

				/*代替え画像の表示*/
				echo '<img src="'. plugins_url("no-image.jpg", __FILE__) .'" />';
 
			}
			?>

		

        			<?php echo $title; ?>
			</a>
        		
    		</li>
		<?php


	}


	?>


	    <br style="clear:both;">
	    </ul>
	</div><!-- inner-search -->


	<?php

}
}



/*****************************************************************
 * 登録されたカスタムフィールド一覧の名前を取得する関数
 *****************************************************************/
function custmField()
{


	//　登録されているカスタムフィールド一覧の配列取得
	global $wpdb;
	$meta_key = $wpdb->get_col("SELECT meta_key FROM $wpdb->postmeta");

	//　頭に「_」がない値の取得
	foreach($meta_key as $meta_keys)
	{


		//　一文字目に「_」があるかの判定、なければ配列にいれる
		$one = mb_substr($meta_keys ,0 ,1);
		if($one != '_')
		{
			$field[] = $meta_keys;
		}


	}


	//　配列$fieldから同じ値の削除をし、設定されたカスタムフィールド名を取得
	$field = array_unique($field);
	return $field;


}

?>