<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Recipes extends CI_Controller {

  public function __construct()
  {
    parent::__construct();
    //load database model
    $this->load->model('recipes_model');
    //check for auth
    if (!$this->ion_auth->logged_in())
    {
      redirect('auth/login');
    }
  }
/*
* http://base_url/recipes
* Shows list of user recipes with edit button and create new recipe button.
* Author: Christopher Frew
*/
  public function index()
  {
    $data['recipes'] = $this->recipes_model->get_recipes();
    $data['recipe_tags'] = $this->recipes_model->get_recipe_tags($data['recipes']);
    $this->load->view('recipes', $data);
  }
/*
* http://base_url/recipes/create/
* Create recipe.
* POST to here to create a recipe.
* Author: Christopher Frew
*/
  public function create()
  {
    $recipe_name = $this->input->post('recipe_name');
    if ($recipe_name !== NULL)    //if $_POST is set
    {
      //Check if Recipe already exists
      $recipe_id = $this->recipes_model->create_recipe($recipe_name)->id; //create entry in recipe table
      redirect("recipes/edit/$recipe_id");
    }
    else
    {
      redirect("recipes"); //else redirect to view all recipes
    }
  }
/*
* http://base_url/recipes/share/$recipe_id
* POST here to share a recipe.
* Author: Christopher Frew
*
*/
  public function share($recipe_id)
  {
    $action = $this->input->post('action');
    //check if recipe belongs to current user
    if ($this->owner_logged_in($recipe_id))
    {
      if ($action === 'share')    //if $_POST is set to share
      {
        $this->recipes_model->share_recipe($recipe_id);
        redirect("recipes");
      }
      else if ($action === 'unshare')
      {
        $this->recipes_model->unshare_recipe($recipe_id);
        redirect("recipes");
      }
    }
    else
    {
      redirect("recipes"); //else redirect to view all recipes
    }
  }
/*
* http://base_url/recipes/view/$recipe_id
* View recipe. Takes one argument, recipe.id
* Author: Christopher Frew
*
*/
  public function view($recipe_id)
  {
    //Redirect if argument is null
    if($recipe_id === NULL)
    {
      redirect('recipes');
    }
    //check if recipe belongs to current user, or is public recipe
    $recipe_public = $this->recipes_model->check_recipe_global($recipe_id);
    if ($this->owner_logged_in($recipe_id) || $recipe_public)
    {
      $data['recipe_ingredients'] = $this->recipes_model->get_recipe_ingredients($recipe_id);
      $data['steps'] = $this->recipes_model->get_recipe_steps($recipe_id);
      $data['recipe_name'] = $this->recipes_model->get_recipe_name($recipe_id)->recipe_name;
      $this->load->view('view_recipe', $data);
    }
  }
/*
* http://base_url/recipes/edit/$recipe_id
* Edit recipe. Takes one argument, recipe.id
* POST to here to edit a recipe
* Author: Christopher Frew
*/
  public function edit($recipe_id = NULL)
  {
    //Redirect if argument is null
    if($recipe_id === NULL)
    {
      redirect('recipes');
    }
    //
    //ADD NEW INGREDIENT
    //
    //Get $_POST variables for adding new ingredient
    $new_ingredient = $this->input->post('ingredient');
    $new_units = $this->input->post('units');
    $new_quantity = $this->input->post('quantity');
    //if $_POST is set
    if ($new_ingredient !== NULL && $new_units !== NULL && $new_quantity !== NULL)
    {
      //check if recipe belongs to current user
      if ($this->owner_logged_in($recipe_id))
      {
        //update existing recipe
        $this->recipes_model->add_ingredient_to_recipe($recipe_id, $new_ingredient, $new_quantity, $new_units);
        //redirect to edit recipe
        redirect("recipes/edit/$recipe_id");
      }
    }
    //
    //DELETE INGREDIENT
    //
    //Get $_POST variables for deleting ingredient
    $recipe_ingredient_id = $this->input->post('deleted_ingredient');
    //if $_POST is set
    if ($recipe_ingredient_id !== NULL)
    {
      //check if recipe belongs to current user
      if ($this->owner_logged_in($recipe_id))
      {
        //delete recipe_ingredient row
        $this->recipes_model->delete_recipe_ingredient($recipe_ingredient_id);
        //redirect to edit recipe
        redirect("recipes/edit/$recipe_id");
      }
    }
    //
    //ADD NEW STEP
    //
    //Get $_POST variables for deleting ingredient
    $step_method = $this->input->post('step_method');
    //if $_POST is set
    if ($step_method !== NULL)
    {
      //check if recipe belongs to current user
      if ($this->owner_logged_in($recipe_id))
      {
        //add new recipe step
        $this->recipes_model->add_recipe_step($recipe_id, $step_method);
        //redirect to edit recipe
        redirect("recipes/edit/$recipe_id");
      }
    }
    //
    //DELETE STEP
    //
    //Get $_POST variables for deleting ingredient
    $deleted_step_id = $this->input->post('deleted_step');
    //if $_POST is set
    if ($deleted_step_id !== NULL)
    {
      //check if recipe belongs to current user
      if ($this->owner_logged_in($recipe_id))
      {
        //delete recipe step
        $this->recipes_model->delete_recipe_step($deleted_step_id);
        //redirect to edit recipe
        redirect("recipes/edit/$recipe_id");
      }
    }
    //
    //RECIPE EDITOR
    //Preloads all recipe data and loads the main editor view
    //
    else
    {
      //check if recipe belongs to current user
      if ($this->owner_logged_in($recipe_id))
      {
        //get recipe data
        $data['recipe_ingredients'] = $this->recipes_model->get_recipe_ingredients($recipe_id);
        $data['steps'] = $this->recipes_model->get_recipe_steps($recipe_id);
        $data['recipe_id'] = $recipe_id;
        $data['units'] = $this->recipes_model->get_units();
        $data['ingredients'] = $this->recipes_model->get_ingredients();
        $data['recipe_name'] = $this->recipes_model->get_recipe_name($recipe_id)->recipe_name;
        //load recipe editor
        $this->load->view('edit_recipe', $data);
      }
    }
  }
/*
* http://base_url/recipes/delete/
* Delete recipe. Takes one argument, recipe.id
* POST to here to delete a recipe.
* Author: Christopher Frew
*/
  public function delete($recipe_id = NULL)
  {
    //Redirect if argument is null
    if($recipe_id === NULL)
    {
      redirect('recipes');
    }

    //if $_POST is set correctly
    $is_delete = $this->input->post('delete');
    if($is_delete === 'Delete')
    {
      //check if recipe belongs to current user
      if ($this->owner_logged_in($recipe_id))
      {
        //delete existing recipe
        $this->recipes_model->delete_recipe($recipe_id);
        redirect('recipes');
      }
    }

    //else
    $data['recipe_id'] = $recipe_id;
    $this->load->view('delete_recipe', $data);
  }
/*
* http://base_url/recipes/import
* Import recipe.
* POST a URL here to attempt an import.
* Author: Shannan Mikic and Christopher Frew
*/
  public function import()
  {
    $url = $this->input->post('url');
    $data['recipes'] = $this->recipes_model->scrape($url);
    $recipe_name = $data['recipes']['title'];

    if ($recipe_name !== NULL)    //if $_POST is set
    {
      $recipe_id = $this->recipes_model->create_recipe($recipe_name)->id; //create entry in recipe table

    // Get Data to Pass
      $data['imported_ingredients'] = $data['recipes']['ingredients'];
      $data['imported_steps'] = $data['recipes']['method'];
      $data['recipe_id'] = $recipe_id;
      $data['units'] = $this->recipes_model->get_units();
      $data['ingredients'] = $this->recipes_model->get_ingredients();
      $data['recipe_name'] = $recipe_name;

    //load recipe editor
      $this->load->view('edit_import', $data);
    }
    else
    {
    //  redirect("recipes"); //else redirect to view all recipes
    }
  }
/*
* http://base_url/recipes/save_import/$recipe_id
* Save an imported recipe.
* POST here to bulk add ingredients and steps to a recipe
* Author: Christopher Frew
*/

  public function save_import($recipe_id)
  {
    $units = $this->input->post('units');
    $ingredients = $this->input->post('ingredients');
    $qty = $this->input->post('quantity');
    $steps = $this->input->post('steps');
    //check $_POST is set correctly
    if ($units !== NULL && $ingredients !== NULL && $qty !== NULL && $steps !== NULL)
    {
      //check if recipe belongs to current user
      if ($this->owner_logged_in($recipe_id))
      {
        //translate POST arrays to multidimensional array
        $recipe_ingredients = array();
        for ($i = 0; $i < sizeof($ingredients); $i++)
        {
          $recipe_ingredients[$i] = array(
            'quantity' => $qty[$i],
            'unit' => $units[$i],
            'ingredient' => $ingredients[$i]
          );
        }

        //add each ingredient to the recipe
        foreach($recipe_ingredients as $rI)
        {
          $this->recipes_model->add_ingredient_to_recipe($recipe_id, $rI['ingredient'], $rI['quantity'], $rI['unit']);
        }

        //add each step to the recipe
        foreach($steps as $step)
        {
          $this->recipes_model->add_recipe_step($recipe_id, $step);
        }

        //go to recipe editor page
        redirect("recipes/edit/$recipe_id");
    }
    else // recipe does not belong to owner, redirect to recipes page
    {
      redirect("recipes");
    }
  }
  else // POST not set correctly, redirect to editor page
  {
    redirect("recipes/edit/$recipe_id");
  }
}
/*
* http://base_url/recipes/search
* Search recipes.
* GET string here to get a list of recipes that cotnain that string in ingredients or in recipe name.
* Author: Shannan Mikic
*/
  public function search()
  {
    $data['recipes'] = $this->recipes_model->search_recipes();
    $data['recipe_tags'] = $this->recipes_model->get_recipe_tags($data['recipes']);

    $this->load->view('search', $data);
  }
/*
* http://base_url/recipes/tag/$recipe_id
* add tag to recipe
* POST here to add $tag_name to $recipe_id
* creates new tag if $tag_name doesn't exist
* Author: Christopher Frew
*/
  public function tag()
  {

  }
/*
* http://base_url/recipes/untag/$recipe_id
* remove tag from recipe
* POST here to remove $tag_name from $recipe_id
* Author: Christopher Frew
*/
  public function untag()
  {

  }

/*
* PRIVATE FUNCTIONS
*/
/*
* Check if $recipe_id belongs to current logged in user
* Author: Christopher Frew
*/
  private function owner_logged_in($recipe_id)
  {
    $curnt_userid = $this->ion_auth->user()->row()->id;
    $recipe_userid = $this->recipes_model->check_recipe_owner($recipe_id);
    if ($recipe_userid[0]->user_id === $curnt_userid)
    {
      return TRUE;
    }
    else
    {
      return FALSE;
    }
  }

}
